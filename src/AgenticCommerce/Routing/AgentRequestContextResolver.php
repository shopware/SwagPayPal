<?php declare(strict_types=1);
/*
 * (c) shopware AG <info@shopware.com>
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Swag\PayPal\AgenticCommerce\Routing;

use Lcobucci\JWT\Validation\Constraint\IssuedBy;
use Lcobucci\JWT\Validation\Constraint\LooseValidAt;
use Lcobucci\JWT\Validation\RequiredConstraintsViolated;
use Shopware\Core\Content\ProductExport\ProductExportCollection;
use Shopware\Core\Framework\Context;
use Shopware\Core\Framework\DataAbstractionLayer\EntityRepository;
use Shopware\Core\Framework\DataAbstractionLayer\Search\Criteria;
use Shopware\Core\Framework\DataAbstractionLayer\Search\Filter\EqualsFilter;
use Shopware\Core\Framework\JWT\Constraints\HasValidRSAJWKSignature;
use Shopware\Core\Framework\JWT\JWTDecoder;
use Shopware\Core\Framework\JWT\JWTException;
use Shopware\Core\Framework\Log\Package;
use Shopware\Core\Framework\Routing\RequestContextResolverInterface;
use Shopware\Core\Framework\Routing\RouteScopeCheckTrait;
use Shopware\Core\Framework\Routing\RouteScopeRegistry;
use Shopware\Core\Framework\Util\Random;
use Shopware\Core\Framework\Validation\Constraint\Uuid;
use Shopware\Core\Framework\Validation\DataValidationDefinition;
use Shopware\Core\Framework\Validation\DataValidator;
use Shopware\Core\Framework\Validation\Exception\ConstraintViolationException;
use Shopware\Core\PlatformRequest;
use Shopware\Core\System\SalesChannel\Context\SalesChannelContextServiceInterface;
use Shopware\Core\System\SalesChannel\Context\SalesChannelContextServiceParameters;
use Swag\PayPal\AgenticCommerce\Exception\AgentException;
use Swag\PayPal\AgenticCommerce\Security\AbstractPayPalJwksProvider;
use Swag\PayPal\AgenticCommerce\Validation\CartTokenValidator;
use Swag\PayPal\AgenticCommerce\Validation\Constraint\PayPalExternalId;
use Swag\PayPal\AgenticCommerce\Validation\HasScopes;
use Swag\PayPal\SwagPayPal;
use Symfony\Component\Clock\NativeClock;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Validator\Constraints\All;
use Symfony\Component\Validator\Constraints\NotBlank;
use Symfony\Component\Validator\Constraints\Optional;
use Symfony\Component\Validator\Constraints\Type;
use Symfony\Component\Validator\Exception\InvalidArgumentException;

/**
 * @internal
 */
#[Package('checkout')]
class AgentRequestContextResolver implements RequestContextResolverInterface
{
    use RouteScopeCheckTrait;

    public const JWT_EXPECTED_ISSUER = 'paypal.com';

    /**
     * @internal
     *
     * @param EntityRepository<ProductExportCollection> $productExportRepository
     */
    public function __construct(
        private readonly DataValidator $validator,
        private readonly EntityRepository $productExportRepository,
        private readonly JWTDecoder $JWTDecoder,
        private readonly RouteScopeRegistry $routeScopeRegistry,
        private readonly SalesChannelContextServiceInterface $contextService,
        private readonly AbstractPayPalJwksProvider $jwksProvider,
    ) {
    }

    public function resolve(Request $request): void
    {
        if ($request->attributes->has(PlatformRequest::ATTRIBUTE_CONTEXT_OBJECT)) {
            return;
        }

        if (!$this->isRequestScoped($request, AgentRouteScope::class)) {
            return;
        }

        $token = $request->headers->get('Authorization');

        if (!$token) {
            throw AgentException::unauthorized('Missing Authorization header');
        }

        $token = $this->extractJwtFromAuthorizationHeader($token);

        $source = $this->resolveContextSource($token);
        $context = new Context($source);

        try {
            $this->validateJWT($request, $token);
        } catch (RequiredConstraintsViolated $e) {
            /** @deprecated tag:v11.0.0 - Remove RequiredConstraintViolated from caught Exceptions, it is a fix for 6.7.0.0 specifically */
            // this is a workaround for the JWTDecoder which does not catch RequiredConstraintsViolated exceptions in 6.7.0.0
            throw AgentException::unauthorized('Invalid JWT token', $e);
        } catch (JWTException $e) {
            throw AgentException::unauthorized('Invalid JWT token', $e->getPrevious());
        }

        $criteria = new Criteria();
        $criteria->addFilter(
            new EqualsFilter('storefrontSalesChannel.active', true),
            new EqualsFilter('salesChannel.active', true),
            new EqualsFilter('salesChannel.typeId', SwagPayPal::SALES_CHANNEL_TYPE_AGENTIC_COMMERCE),
        );

        $productExport = $this->productExportRepository->search($criteria, $context)->getEntities()->first();
        if (!$productExport) {
            throw AgentException::unauthorized('Sales channel not found');
        }

        $source->setStreamId($productExport->getProductStreamId());

        preg_match(\sprintf('/%s/', CartTokenValidator::REGEX), $request->getPathInfo(), $matches);

        $salesChannelContext = $this->contextService->get(new SalesChannelContextServiceParameters(
            salesChannelId: $productExport->getStorefrontSalesChannelId(),
            token: $matches[1] ?? Random::getAlphanumericString(32),
            originalContext: $context,
        ));

        $request->attributes->set(PlatformRequest::ATTRIBUTE_CONTEXT_OBJECT, $context);
        $request->attributes->set(PlatformRequest::ATTRIBUTE_SALES_CHANNEL_CONTEXT_OBJECT, $salesChannelContext);
    }

    protected function getScopeRegistry(): RouteScopeRegistry
    {
        return $this->routeScopeRegistry;
    }

    private function validateJWT(Request $request, string $jwt): void
    {
        try {
            $this->validateJWTWithJwks($request, $jwt);
        } catch (JWTException $e) {
            if (!$this->shouldRefreshJwks($e)) {
                throw $e;
            }

            $this->validateJWTWithJwks($request, $jwt, true);
        }
    }

    private function validateJWTWithJwks(Request $request, string $jwt, bool $refreshJwks = false): void
    {
        /** @var list<string> $scopes */
        $scopes = $request->attributes->get(AgentRouteScope::ATTRIBUTE_PAYPAL_AGENT_SCOPE, []);

        $constraints = [
            new IssuedBy(self::JWT_EXPECTED_ISSUER),
            new LooseValidAt(new NativeClock()),
            new HasValidRSAJWKSignature($this->jwksProvider->getJwks($refreshJwks)),
        ];

        if ($scopes !== []) {
            $constraints[] = new HasScopes($scopes);
        }

        $this->JWTDecoder->validate($jwt, ...$constraints);
    }

    private function shouldRefreshJwks(JWTException $exception): bool
    {
        return \str_contains($exception->getMessage(), 'Key ID')
            || \str_contains($exception->getMessage(), 'signature')
            || \str_contains($exception->getMessage(), 'Invalid JWK');
    }

    private function extractJwtFromAuthorizationHeader(string $authorization): string
    {
        $authorization = trim($authorization);

        // Accept both: "Bearer <jwt>" and "<jwt>" (backward compatible)
        if (\preg_match('/^Bearer\s+(.+)$/i', $authorization, $m) === 1) {
            return trim($m[1]);
        }

        return $authorization;
    }

    private function resolveContextSource(string $token): AgentSource
    {
        try {
            /** @var array{external_id: list<mixed>, sub: string, iat: \DateTimeInterface, exp: \DateTimeInterface, scope: list<string>, debug_id?: string} $decoded */
            $decoded = $this->JWTDecoder->decode($token); // @phpstan-ignore varTag.type
        } catch (JWTException $e) {
            throw AgentException::unauthorized('Invalid JWT token', $e->getPrevious());
        }

        $definition = new DataValidationDefinition('paypal.agent_source');
        $definition
            ->add('external_id', new NotBlank(), new Type('array'), new PayPalExternalId())
            ->add('sub', new NotBlank(), new Type('string'), new Uuid())
            ->add('iat', new NotBlank(), new Type(\DateTimeInterface::class))
            ->add('exp', new NotBlank(), new Type(\DateTimeInterface::class))
            ->add('scope', new Type('array'), new All(constraints: [new Type('string'), new NotBlank()]))
            ->add('debug_id', new Optional([new Type('string')]));

        try {
            $this->validator->validate($decoded, $definition);
        } catch (ConstraintViolationException $e) {
            throw AgentException::unauthorized('Invalid JWT token', $e);
        } catch (InvalidArgumentException $e) {
            /** @deprecated tag:v11.0.0 - With Shopware v6.7.2.0 this exception will be caught and processed */
            throw AgentException::unauthorized('Invalid JWT token', $e);
        }

        return new AgentSource(self::extractPayPalMerchantId($decoded['external_id']), $decoded['iat'], $decoded['exp'], $decoded['scope'], $decoded['sub'], $decoded['debug_id'] ?? null);
    }

    /**
     * @param list<mixed> $externalIds
     */
    private static function extractPayPalMerchantId(array $externalIds): string
    {
        foreach ($externalIds as $entry) {
            if (!\is_string($entry) || !\str_starts_with($entry, 'PayPal:')) {
                continue;
            }

            if (\preg_match('/^PayPal:\s*(.+)$/', $entry, $m) === 1) {
                return $m[1];
            }
        }

        throw AgentException::unauthorized('external_id must contain at least one PayPal:* entry.');
    }
}
