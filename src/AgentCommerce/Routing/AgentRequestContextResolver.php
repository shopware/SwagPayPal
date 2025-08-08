<?php declare(strict_types=1);
/*
 * (c) shopware AG <info@shopware.com>
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Swag\PayPal\AgentCommerce\Routing;

use Lcobucci\JWT\Signer\Key\InMemory;
use Lcobucci\JWT\Signer\Rsa\Sha512;
use Lcobucci\JWT\Validation\Constraint\LooseValidAt;
use Lcobucci\JWT\Validation\Constraint\SignedWith;
use Lcobucci\JWT\Validation\RequiredConstraintsViolated;
use Shopware\Core\Content\ProductExport\ProductExportCollection;
use Shopware\Core\Framework\Context;
use Shopware\Core\Framework\DataAbstractionLayer\EntityRepository;
use Shopware\Core\Framework\DataAbstractionLayer\Search\Criteria;
use Shopware\Core\Framework\DataAbstractionLayer\Search\Filter\EqualsFilter;
use Shopware\Core\Framework\JWT\JWTDecoder;
use Shopware\Core\Framework\JWT\JWTException;
use Shopware\Core\Framework\Log\Package;
use Shopware\Core\Framework\Routing\RequestContextResolverInterface;
use Shopware\Core\Framework\Routing\RouteScopeCheckTrait;
use Shopware\Core\Framework\Routing\RouteScopeRegistry;
use Shopware\Core\Framework\Uuid\Uuid;
use Shopware\Core\PlatformRequest;
use Shopware\Core\System\SalesChannel\Context\SalesChannelContextService;
use Shopware\Core\System\SalesChannel\Context\SalesChannelContextServiceParameters;
use Swag\PayPal\AgentCommerce\Exception\AgentException;
use Swag\PayPal\AgentCommerce\Validation\HasScopes;
use Swag\PayPal\SwagPayPal;
use Symfony\Component\Clock\NativeClock;
use Symfony\Component\HttpFoundation\Request;

/**
 * @internal
 */
#[Package('checkout')]
class AgentRequestContextResolver implements RequestContextResolverInterface
{
    use RouteScopeCheckTrait;

    /**
     * This is a hardcoded public key for PayPal JWT validation
     * We use this as long as PayPal does not provide a way to retrieve the public key dynamically
     *
     * @var non-empty-string
     */
    public static string $PAYPAL_JWT = '-----BEGIN PUBLIC KEY-----
MIIBIjANBgkqhkiG9w0BAQEFAAOCAQ8AMIIBCgKCAQEAu1SU1LfVLPHCozMxH2Mo
4lgOEePzNm0tRgeLezV6ffAt0gunVTLw7onLRnrq0/IzW7yWR7QkrmBL7jTKEn5u
+qKhbwKfBstIs+bMY2Zkp18gnTxKLxoS2tFczGkPLPgizskuemMghRniWaoLcyeh
kd3qqGElvW/VDL5AaWTg0nLVkjRo9z+40RQzuVaE8AkAFmxZzow3x+VJYKdjykkJ
0iT9wCS0DRTXu269V264Vf/3jvredZiKRkgwlL9xNAwxXFg0x/XFw005UWVRIkdg
cKWTjpBP2dPwVZ4WWC+9aGVd+Gyn1o0CLelf4rEjGoXbAAEgAqeGUxrcIlbjXfbc
mwIDAQAB
-----END PUBLIC KEY-----';

    /**
     * @internal
     *
     * @param EntityRepository<ProductExportCollection> $productExportRepository
     */
    public function __construct(
        private readonly JWTDecoder $JWTDecoder,
        private readonly RouteScopeRegistry $routeScopeRegistry,
        private readonly EntityRepository $productExportRepository,
        private readonly SalesChannelContextService $contextService,
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

        $source = $this->resolveContextSource($token);

        $criteria = new Criteria();
        $criteria->addFilter(
            new EqualsFilter('storefrontSalesChannel.active', true),
            new EqualsFilter('salesChannel.active', true),
            new EqualsFilter('salesChannel.typeId', SwagPayPal::SALES_CHANNEL_TYPE_AGENT_COMMERCE),
            new EqualsFilter('salesChannel.id', $source->salesChannelId),
        );

        $context = new Context($source);
        $productExport = $this->productExportRepository->search($criteria, $context)->first();
        if (!$productExport) {
            throw AgentException::unauthorized('Sales channel not found');
        }

        preg_match('/CART-(\w+)/', $request->getPathInfo(), $matches);

        $salesChannelContext = $this->contextService->get(new SalesChannelContextServiceParameters(
            salesChannelId: $productExport->getStorefrontSalesChannelId(),
            token: $matches[1] ?? Uuid::randomHex(),
            originalContext: $context
        ));
        $salesChannelContext->getSalesChannel()->setProductExports(new ProductExportCollection([$productExport]));

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
            new LooseValidAt(new NativeClock()),
            new SignedWith(new Sha512(), InMemory::plainText(self::$PAYPAL_JWT)),
        ];

        if (!empty($scopes)) {
            $constraints[] = new HasScopes($scopes);
        }

        $this->JWTDecoder->validate($jwt, ...$constraints);
    }

    private function resolveContextSource(string $token): AgentSource
    {
        try {
            $decoded = $this->JWTDecoder->decode($token);
        } catch (JWTException $e) {
            throw AgentException::unauthorized('Invalid JWT token', $e->getPrevious());
        }

        if (!isset($decoded['paypalMerchantId'], $decoded['iat'], $decoded['exp'], $decoded['shopwareMerchantId'])) {
            throw AgentException::unauthorized('Invalid JWT token');
        }

        if (
            !\is_string($decoded['paypalMerchantId']) || empty($decoded['paypalMerchantId'])
            || !\is_string($decoded['shopwareMerchantId']) || empty($decoded['shopwareMerchantId'])
            || !\is_array($decoded['scope']) || empty($decoded['scope'])
        ) {
            throw AgentException::unauthorized('Invalid JWT token');
        }

        $iat = $decoded['iat'];
        $exp = $decoded['exp'];

        if (!$iat instanceof \DateTimeInterface || !$exp instanceof \DateTimeInterface) {
            throw AgentException::unauthorized('Invalid JWT token');
        }

        $debugId = null;

        if (isset($decoded['debug_id']) && \is_string($decoded['debug_id'])) {
            $debugId = $decoded['debug_id'];
        }

        return new AgentSource($decoded['paypalMerchantId'], $iat, $exp, $decoded['scope'], $decoded['shopwareMerchantId'], $debugId);
    }
}
