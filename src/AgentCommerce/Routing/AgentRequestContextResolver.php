<?php declare(strict_types=1);
/*
 * (c) shopware AG <info@shopware.com>
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Swag\PayPal\AgentCommerce\Routing;

use Lcobucci\JWT\Encoding\JoseEncoder;
use Lcobucci\JWT\Signer\Key\InMemory;
use Lcobucci\JWT\Signer\Rsa\Sha256;
use Lcobucci\JWT\Token\Parser;
use Lcobucci\JWT\UnencryptedToken;
use Lcobucci\JWT\Validation\Constraint;
use Lcobucci\JWT\Validation\Constraint\IssuedBy;
use Lcobucci\JWT\Validation\Constraint\LooseValidAt;
use Lcobucci\JWT\Validation\Constraint\SignedWith;
use Lcobucci\JWT\Validation\RequiredConstraintsViolated;
use Lcobucci\JWT\Validation\Validator;
use Shopware\Core\Content\ProductExport\ProductExportCollection;
use Shopware\Core\Content\ProductExport\ProductExportEntity;
use Shopware\Core\Framework\Context;
use Shopware\Core\Framework\DataAbstractionLayer\EntityRepository;
use Shopware\Core\Framework\DataAbstractionLayer\Search\Criteria;
use Shopware\Core\Framework\DataAbstractionLayer\Search\Filter\EqualsFilter;
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
use Swag\PayPal\AgentCommerce\Exception\AgentException;
use Swag\PayPal\AgentCommerce\Exception\JWTException;
use Swag\PayPal\AgentCommerce\Validation\CartTokenValidator;
use Swag\PayPal\AgentCommerce\Validation\Constraint\PayPalExternalId;
use Swag\PayPal\AgentCommerce\Validation\HasScopes;
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
     * This is a hardcoded public key for PayPal JWT validation
     * We use this as long as PayPal does not provide a way to retrieve the public key dynamically
     *
     * @var non-empty-string
     */
    public static string $PAYPAL_JWT = '-----BEGIN PUBLIC KEY-----
MIIBIjANBgkqhkiG9w0BAQEFAAOCAQ8AMIIBCgKCAQEAvv7Pi1nWWrJj4n5+6gX9
B7BQpctaPEg9VdVK1kzc9xBNwZobeWEgEmiUGtkrn8S5R6Q4NmB4hnb8F5jeCX5O
kyA49mgzw4wNXUPGTGMY5Eoxt9zu1Heaivkljh4+wN6d01oIFkHT6E7VjEJOG2RA
49t7fgQ1phJIUK39B0RAXIG2pYicbujeiiJ12iQipMjY/TVD0KZgUc2Vj2apk7Dv
1YBqFG+HlSG5hWu880IzGQE9Pds5qekIawJJyed08otq29hDHlFd28B0fFhdzcu8
cN83NxddXBlh77b8+a7gaWC5/Iw45THRpIsiG41uX0r0INEDcnR3qCUkz6m9LOVW
kQIDAQAB
-----END PUBLIC KEY-----';

    /**
     * @internal
     *
     * @param EntityRepository<ProductExportCollection> $productExportRepository
     */
    public function __construct(
        private readonly DataValidator $validator,
        private readonly EntityRepository $productExportRepository,
        private readonly RouteScopeRegistry $routeScopeRegistry,
        private readonly SalesChannelContextServiceInterface $contextService,
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

        $criteria = new Criteria();
        $criteria->addFilter(
            new EqualsFilter('storefrontSalesChannel.active', true),
            new EqualsFilter('salesChannel.active', true),
            new EqualsFilter('salesChannel.typeId', SwagPayPal::SALES_CHANNEL_TYPE_AGENT_COMMERCE),
        );

        /** @var ProductExportEntity|null $productExport */
        $productExport = $this->productExportRepository->search($criteria, $context)->first();
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

        try {
            $this->validateJWT($request, $token);
        } catch (RequiredConstraintsViolated $e) {
            /** @deprecated tag:v11.0.0 - Remove RequiredConstraintViolated from caught Exceptions, it is a fix for 6.7.0.0 specifically */
            // this is a workaround for the JWTDecoder which does not catch RequiredConstraintsViolated exceptions in 6.7.0.0
            throw AgentException::unauthorized('Invalid JWT token', $e);
        } catch (JWTException $e) {
            throw AgentException::unauthorized('Invalid JWT token', $e->getPrevious());
        }
    }

    protected function getScopeRegistry(): RouteScopeRegistry
    {
        return $this->routeScopeRegistry;
    }

    private function validateJWT(Request $request, string $jwt): void
    {
        /** @var list<string> $scopes */
        $scopes = $request->attributes->get(AgentRouteScope::ATTRIBUTE_PAYPAL_AGENT_SCOPE, []);

        $constraints = [
            new IssuedBy(self::JWT_EXPECTED_ISSUER),
            new LooseValidAt(new NativeClock()),
            new SignedWith(new Sha256(), InMemory::plainText(self::$PAYPAL_JWT)),
        ];

        if (!empty($scopes)) {
            $constraints[] = new HasScopes($scopes);
        }

        $this->validate($jwt, ...$constraints);
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
            $decoded = $this->decode($token);
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
        } catch (ConstraintViolationException|InvalidArgumentException $e) {
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

    private function decode(string $jwt): array
    {
        return $this->parseToken($jwt)->claims()->all();
    }

    private function validate(string $jwt, Constraint ...$constraints): void
    {
        try {
            $validator = new Validator();
            $validator->assert($this->parseToken($jwt), ...$constraints);
        } catch (RequiredConstraintsViolated $e) {
            throw JWTException::invalidJwt($e->getMessage(), $e);
        }
    }

    private function parseToken(string $jwt): UnencryptedToken
    {
        if (!$jwt) {
            throw JWTException::invalidJwt('JWT cannot be empty');
        }

        try {
            $parser = new Parser(new JoseEncoder());
            $token = $parser->parse($jwt);
        } catch (\Exception $e) {
            throw JWTException::invalidJwt($e->getMessage(), $e);
        }

        if (!$token instanceof UnencryptedToken) {
            throw JWTException::invalidJwt('Incorrect token type');
        }

        return $token;
    }
}
