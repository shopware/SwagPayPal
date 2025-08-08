<?php declare(strict_types=1);
/*
 * (c) shopware AG <info@shopware.com>
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Swag\PayPal\Tests\AgentCommerce\Routing;

use Lcobucci\JWT\Configuration;
use Lcobucci\JWT\Signer\Key\InMemory;
use Lcobucci\JWT\Signer\Rsa\Sha512;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;
use Shopware\Core\Content\ProductExport\ProductExportCollection;
use Shopware\Core\Content\ProductExport\ProductExportDefinition;
use Shopware\Core\Content\ProductExport\ProductExportEntity;
use Shopware\Core\Framework\Context;
use Shopware\Core\Framework\DataAbstractionLayer\EntityRepository;
use Shopware\Core\Framework\DataAbstractionLayer\Search\Criteria;
use Shopware\Core\Framework\DataAbstractionLayer\Search\EntitySearchResult;
use Shopware\Core\Framework\JWT\JWTDecoder;
use Shopware\Core\Framework\Log\Package;
use Shopware\Core\Framework\Routing\RouteScope;
use Shopware\Core\Framework\Routing\RouteScopeRegistry;
use Shopware\Core\Framework\Uuid\Uuid;
use Shopware\Core\PlatformRequest;
use Shopware\Core\System\SalesChannel\Context\SalesChannelContextService;
use Shopware\Core\System\SalesChannel\Context\SalesChannelContextServiceParameters;
use Shopware\Core\System\SalesChannel\SalesChannelContext;
use Shopware\Core\Test\Generator;
use Swag\PayPal\AgentCommerce\Exception\AgentException;
use Swag\PayPal\AgentCommerce\Routing\AgentRequestContextResolver;
use Swag\PayPal\AgentCommerce\Routing\AgentRouteScope;
use Swag\PayPal\AgentCommerce\Routing\AgentSource;
use Symfony\Component\HttpFoundation\Request;

/**
 * @internal
 */
#[Package('checkout')]
#[CoversClass(AgentRequestContextResolver::class)]
class AgentRequestContextResolverTest extends TestCase
{
    private const JWT_PUBLIC = '-----BEGIN PUBLIC KEY-----
MIIBIjANBgkqhkiG9w0BAQEFAAOCAQ8AMIIBCgKCAQEAu1SU1LfVLPHCozMxH2Mo
4lgOEePzNm0tRgeLezV6ffAt0gunVTLw7onLRnrq0/IzW7yWR7QkrmBL7jTKEn5u
+qKhbwKfBstIs+bMY2Zkp18gnTxKLxoS2tFczGkPLPgizskuemMghRniWaoLcyeh
kd3qqGElvW/VDL5AaWTg0nLVkjRo9z+40RQzuVaE8AkAFmxZzow3x+VJYKdjykkJ
0iT9wCS0DRTXu269V264Vf/3jvredZiKRkgwlL9xNAwxXFg0x/XFw005UWVRIkdg
cKWTjpBP2dPwVZ4WWC+9aGVd+Gyn1o0CLelf4rEjGoXbAAEgAqeGUxrcIlbjXfbc
mwIDAQAB
-----END PUBLIC KEY-----';

    private const JWT_PRIVATE = '-----BEGIN PRIVATE KEY-----
MIIEvwIBADANBgkqhkiG9w0BAQEFAASCBKkwggSlAgEAAoIBAQC7VJTUt9Us8cKj
MzEfYyjiWA4R4/M2bS1GB4t7NXp98C3SC6dVMvDuictGeurT8jNbvJZHtCSuYEvu
NMoSfm76oqFvAp8Gy0iz5sxjZmSnXyCdPEovGhLa0VzMaQ8s+CLOyS56YyCFGeJZ
qgtzJ6GR3eqoYSW9b9UMvkBpZODSctWSNGj3P7jRFDO5VoTwCQAWbFnOjDfH5Ulg
p2PKSQnSJP3AJLQNFNe7br1XbrhV//eO+t51mIpGSDCUv3E0DDFcWDTH9cXDTTlR
ZVEiR2BwpZOOkE/Z0/BVnhZYL71oZV34bKfWjQIt6V/isSMahdsAASACp4ZTGtwi
VuNd9tybAgMBAAECggEBAKTmjaS6tkK8BlPXClTQ2vpz/N6uxDeS35mXpqasqskV
laAidgg/sWqpjXDbXr93otIMLlWsM+X0CqMDgSXKejLS2jx4GDjI1ZTXg++0AMJ8
sJ74pWzVDOfmCEQ/7wXs3+cbnXhKriO8Z036q92Qc1+N87SI38nkGa0ABH9CN83H
mQqt4fB7UdHzuIRe/me2PGhIq5ZBzj6h3BpoPGzEP+x3l9YmK8t/1cN0pqI+dQwY
dgfGjackLu/2qH80MCF7IyQaseZUOJyKrCLtSD/Iixv/hzDEUPfOCjFDgTpzf3cw
ta8+oE4wHCo1iI1/4TlPkwmXx4qSXtmw4aQPz7IDQvECgYEA8KNThCO2gsC2I9PQ
DM/8Cw0O983WCDY+oi+7JPiNAJwv5DYBqEZB1QYdj06YD16XlC/HAZMsMku1na2T
N0driwenQQWzoev3g2S7gRDoS/FCJSI3jJ+kjgtaA7Qmzlgk1TxODN+G1H91HW7t
0l7VnL27IWyYo2qRRK3jzxqUiPUCgYEAx0oQs2reBQGMVZnApD1jeq7n4MvNLcPv
t8b/eU9iUv6Y4Mj0Suo/AU8lYZXm8ubbqAlwz2VSVunD2tOplHyMUrtCtObAfVDU
AhCndKaA9gApgfb3xw1IKbuQ1u4IF1FJl3VtumfQn//LiH1B3rXhcdyo3/vIttEk
48RakUKClU8CgYEAzV7W3COOlDDcQd935DdtKBFRAPRPAlspQUnzMi5eSHMD/ISL
DY5IiQHbIH83D4bvXq0X7qQoSBSNP7Dvv3HYuqMhf0DaegrlBuJllFVVq9qPVRnK
xt1Il2HgxOBvbhOT+9in1BzA+YJ99UzC85O0Qz06A+CmtHEy4aZ2kj5hHjECgYEA
mNS4+A8Fkss8Js1RieK2LniBxMgmYml3pfVLKGnzmng7H2+cwPLhPIzIuwytXywh
2bzbsYEfYx3EoEVgMEpPhoarQnYPukrJO4gwE2o5Te6T5mJSZGlQJQj9q4ZB2Dfz
et6INsK0oG8XVGXSpQvQh3RUYekCZQkBBFcpqWpbIEsCgYAnM3DQf3FJoSnXaMhr
VBIovic5l0xFkEHskAjFTevO86Fsz1C2aSeRKSqGFoOQ0tmJzBEs1R6KqnHInicD
TQrKhArgLXX4v3CddjfTRJkFWDbE/CkvKZNOrcf1nhaGCPspRJj2KUkj1Fhl9Cnc
dn/RsYEONbwQSjIfMPkvxF+8HQ==
-----END PRIVATE KEY-----';

    protected function tearDown(): void
    {
        // TODO: Remove this when we have a way to retrieve the public key dynamically from PayPal
        // Reset the static variable to the original value
        AgentRequestContextResolver::$PAYPAL_JWT = self::JWT_PUBLIC;
    }

    public function testResolveWithContextIsSkipped(): void
    {
        $context = Context::createDefaultContext();
        $request = new Request();
        $request->attributes->set(PlatformRequest::ATTRIBUTE_CONTEXT_OBJECT, $context);

        $resolver = new AgentRequestContextResolver(
            new JWTDecoder(),
            new RouteScopeRegistry([]),
            $this->createMock(EntityRepository::class),
            $this->createMock(SalesChannelContextService::class),
        );

        $resolver->resolve($request);

        static::assertSame($context, $request->attributes->get(PlatformRequest::ATTRIBUTE_CONTEXT_OBJECT));
    }

    public function testResolveWithWrongScopeDoesNothing(): void
    {
        $request = new Request();
        $request->attributes->set(PlatformRequest::ATTRIBUTE_ROUTE_SCOPE, ['wrong-scope']);
        $request->attributes->set(AgentRouteScope::ATTRIBUTE_PAYPAL_AGENT_SCOPE, ['cart', 'checkout']);

        $wrongScope = $this->createMock(RouteScope::class);
        $wrongScope
            ->method('getId')
            ->willReturn('wrong-scope');

        $resolver = new AgentRequestContextResolver(
            new JWTDecoder(),
            new RouteScopeRegistry([new AgentRouteScope(), $wrongScope]),
            $this->createMock(EntityRepository::class),
            $this->createMock(SalesChannelContextService::class),
        );

        $resolver->resolve($request);

        static::assertFalse($request->attributes->has(PlatformRequest::ATTRIBUTE_CONTEXT_OBJECT));
    }

    public function testResolveWithMissingAuthorizationHeader(): void
    {
        $request = new Request();
        $request->attributes->set(PlatformRequest::ATTRIBUTE_ROUTE_SCOPE, [AgentRouteScope::ID]);
        $request->attributes->set(AgentRouteScope::ATTRIBUTE_PAYPAL_AGENT_SCOPE, ['cart', 'checkout']);

        $resolver = new AgentRequestContextResolver(
            new JWTDecoder(),
            new RouteScopeRegistry([new AgentRouteScope()]),
            $this->createMock(EntityRepository::class),
            $this->createMock(SalesChannelContextService::class),
        );

        $this->expectExceptionObject(AgentException::unauthorized('Missing Authorization header'));

        $resolver->resolve($request);
    }

    public function testResolveWithWrongPublicJWT(): void
    {
        $jwt = self::encodeJWT(
            'MERCHANT_ID',
            new \DateTimeImmutable(),
            new \DateTimeImmutable('+1 hour'),
            ['cart', 'checkout'],
            'SALES_CHANNEL_ID'
        );

        $request = new Request();
        $request->headers->set('Authorization', $jwt);
        $request->attributes->set(PlatformRequest::ATTRIBUTE_ROUTE_SCOPE, [AgentRouteScope::ID]);
        $request->attributes->set(AgentRouteScope::ATTRIBUTE_PAYPAL_AGENT_SCOPE, ['cart', 'checkout']);

        // this is a wrong public key
        // TODO: change this up in the future, but this has to do for now, as long we do not have a real public JWT key from PayPal
        AgentRequestContextResolver::$PAYPAL_JWT = '-----BEGIN PUBLIC KEY-----
MIIBIjANBgkqhkiG9w0BAQEFAAOCAQ8AMIIBCgKCAQEAu1SU1LfVLPHCozMxH2Mf
4lgOEePzNm0tRgeLezV6ffAt0gunVTLw7onLRnrq0/IzW7yWR7QkrmBL7jTKEn5u
+qKhbwKfBstIs+bMY2Zkp18gnTxKLxoS2tFczGkPLPgizskuemMghRniWaoLcyeh
kd3qqGElvW/VDL5AaWTg0nLVkjRo9z+40RQzuVaE8AkAFmxZzow3x+VJYKdjykkJ
0iT9wCS0DRTXu269V264Vf/3jvredZiKRkgwlL9xNAwxXFg0x/XFw005UWVRIkdg
cKWTjpBP2dPwVZ4WWC+9aGVd+Gyn1o0CLelf4rEjGoXbAAEgAqeGUxrcIlbjXfbc
mwIDAQAB
-----END PUBLIC KEY-----';

        $export = new ProductExportEntity();
        $export->setId(Uuid::randomHex());
        $export->setStorefrontSalesChannelId(Uuid::randomHex());
        $entityRepository = $this->createMock(EntityRepository::class);
        $entityRepository
            ->expects(static::once())
            ->method('search')
            ->willReturn(self::createSearchResult($export));

        $resolver = new AgentRequestContextResolver(
            new JWTDecoder(),
            new RouteScopeRegistry([new AgentRouteScope()]),
            $entityRepository,
            $this->createMock(SalesChannelContextService::class),
        );

        $this->expectExceptionObject(AgentException::unauthorized('Invalid JWT'));

        $resolver->resolve($request);
    }

    public function testResolveWithExpiredToken(): void
    {
        $iat = new \DateTimeImmutable('-2 hours');
        $exp = new \DateTimeImmutable('-1 hour');

        $jwt = self::encodeJWT(
            'MERCHANT_ID',
            $iat,
            $exp,
            ['cart', 'checkout'],
            'SALES_CHANNEL_ID'
        );

        $request = new Request();
        $request->headers->set('Authorization', $jwt);
        $request->attributes->set(PlatformRequest::ATTRIBUTE_ROUTE_SCOPE, [AgentRouteScope::ID]);
        $request->attributes->set(AgentRouteScope::ATTRIBUTE_PAYPAL_AGENT_SCOPE, ['cart', 'checkout']);

        $export = new ProductExportEntity();
        $export->setId(Uuid::randomHex());
        $export->setStorefrontSalesChannelId(Uuid::randomHex());
        $entityRepository = $this->createMock(EntityRepository::class);
        $entityRepository
            ->expects(static::once())
            ->method('search')
            ->willReturn(self::createSearchResult($export));

        $resolver = new AgentRequestContextResolver(
            new JWTDecoder(),
            new RouteScopeRegistry([new AgentRouteScope()]),
            $entityRepository,
            $this->createMock(SalesChannelContextService::class),
        );

        $this->expectExceptionObject(AgentException::unauthorized('Invalid JWT token'));

        $resolver->resolve($request);
    }

    public function testResolveWithWrongJWTHeader(): void
    {
        $request = new Request();
        $request->headers->set('Authorization', 'ey.wrong.jwt');
        $request->attributes->set(PlatformRequest::ATTRIBUTE_ROUTE_SCOPE, [AgentRouteScope::ID]);
        $request->attributes->set(AgentRouteScope::ATTRIBUTE_PAYPAL_AGENT_SCOPE, ['cart', 'checkout']);

        $resolver = new AgentRequestContextResolver(
            new JWTDecoder(),
            new RouteScopeRegistry([new AgentRouteScope()]),
            $this->createMock(EntityRepository::class),
            $this->createMock(SalesChannelContextService::class),
        );

        $this->expectExceptionObject(AgentException::unauthorized('Invalid JWT token'));

        $resolver->resolve($request);
    }

    /**
     * @param array<string, mixed> $claims
     */
    #[DataProvider('malformedJWTProvider')]
    public function testResolveWithMalformedJWTClaims(array $claims): void
    {
        $token = self::encodeJWT(
            $claims['paypalMerchantId'] ?? null,
            $claims['iat'] ?? null,
            $claims['exp'] ?? null,
            $claims['scope'] ?? null,
            $claims['shopwareMerchantId'] ?? null
        );

        $request = new Request();
        $request->headers->set('Authorization', $token);
        $request->attributes->set(PlatformRequest::ATTRIBUTE_ROUTE_SCOPE, [AgentRouteScope::ID]);
        $request->attributes->set(AgentRouteScope::ATTRIBUTE_PAYPAL_AGENT_SCOPE, ['cart', 'checkout']);

        $resolver = new AgentRequestContextResolver(
            new JWTDecoder(),
            new RouteScopeRegistry([new AgentRouteScope()]),
            $this->createMock(EntityRepository::class),
            $this->createMock(SalesChannelContextService::class),
        );

        $this->expectExceptionObject(AgentException::unauthorized('Invalid JWT token'));

        $resolver->resolve($request);
    }

    public static function malformedJWTProvider(): \Generator
    {
        yield 'Missing all' => [[]];

        yield 'Missing paypalMerchantId' => [['iat' => new \DateTimeImmutable(), 'exp' => new \DateTimeImmutable('+1 hour'), 'scope' => ['cart', 'checkout'], 'shopwareMerchantId' => 'SALES_CHANNEL_ID']];
        yield 'Missing iat' => [['paypalMerchantId' => 'MERCHANT_ID', 'exp' => new \DateTimeImmutable('+1 hour'), 'scope' => ['cart', 'checkout'], 'shopwareMerchantId' => 'SALES_CHANNEL_ID']];
        yield 'Missing exp' => [['paypalMerchantId' => 'MERCHANT_ID', 'iat' => new \DateTimeImmutable(), 'scope' => ['cart', 'checkout'], 'shopwareMerchantId' => 'SALES_CHANNEL_ID']];

        yield 'Empty paypalMerchantId' => [['paypalMerchantId' => '', 'iat' => new \DateTimeImmutable(), 'exp' => new \DateTimeImmutable('+1 hour'), 'scope' => ['cart', 'checkout'], 'shopwareMerchantId' => 'SALES_CHANNEL_ID']];
        yield 'Empty salesChannelId' => [['paypalMerchantId' => 'MERCHANT_ID', 'iat' => new \DateTimeImmutable(), 'exp' => new \DateTimeImmutable('+1 hour'), 'scope' => []]];
    }

    public function testResolveWithWrongAgentScopeInRoute(): void
    {
        $jwt = self::encodeJWT(
            'MERCHANT_ID',
            new \DateTimeImmutable(),
            new \DateTimeImmutable('+1 hour'),
            ['cart', 'checkout'],
            'SALES_CHANNEL_ID'
        );

        $request = new Request();
        $request->headers->set('Authorization', $jwt);
        $request->attributes->set(PlatformRequest::ATTRIBUTE_ROUTE_SCOPE, [AgentRouteScope::ID]);
        $request->attributes->set(AgentRouteScope::ATTRIBUTE_PAYPAL_AGENT_SCOPE, ['wrong-scope']);

        $export = new ProductExportEntity();
        $export->setId(Uuid::randomHex());
        $export->setStorefrontSalesChannelId(Uuid::randomHex());
        $entityRepository = $this->createMock(EntityRepository::class);
        $entityRepository
            ->expects(static::once())
            ->method('search')
            ->willReturn(self::createSearchResult($export));

        $resolver = new AgentRequestContextResolver(
            new JWTDecoder(),
            new RouteScopeRegistry([new AgentRouteScope()]),
            $entityRepository,
            $this->createMock(SalesChannelContextService::class),
        );

        $this->expectExceptionObject(AgentException::unauthorized('Invalid JWT token'));

        $resolver->resolve($request);
    }

    public function testResolveWithWrongAgentScopeInRequest(): void
    {
        $iat = new \DateTimeImmutable();
        $exp = new \DateTimeImmutable('+1 hour');

        $jwt = self::encodeJWT(
            'MERCHANT_ID',
            $iat,
            $exp,
            ['these', 'are', 'wrong', 'scopes'],
            'SALES_CHANNEL_ID'
        );

        $request = Request::create('/CART-12345678912345678912345678912345/foo-bar');
        $request->headers->set('Authorization', $jwt);
        $request->attributes->set(PlatformRequest::ATTRIBUTE_ROUTE_SCOPE, [AgentRouteScope::ID]);
        $request->attributes->set(AgentRouteScope::ATTRIBUTE_PAYPAL_AGENT_SCOPE, ['wrong-scope']);

        $expectedSource = new AgentSource(
            'MERCHANT_ID',
            $iat,
            $exp,
            ['these', 'are', 'wrong', 'scopes'],
            'SALES_CHANNEL_ID',
        );

        $expectedContext = new Context($expectedSource);

        $productExport = new ProductExportEntity();
        $productExport->setId(Uuid::randomHex());
        $productExport->setSalesChannelId('SALES_CHANNEL_ID');
        $productExport->setStorefrontSalesChannelId('SALES_CHANNEL_ID');

        $productExportResult = new EntitySearchResult(
            ProductExportDefinition::ENTITY_NAME,
            1,
            new ProductExportCollection([$productExport]),
            null,
            new Criteria(),
            $expectedContext
        );

        $repo = $this->createMock(EntityRepository::class);
        $repo
            ->expects(static::once())
            ->method('search')
            ->with(static::isInstanceOf(Criteria::class), $expectedContext)
            ->willReturn($productExportResult);

        $expectedSalesChannelContextParameters = new SalesChannelContextServiceParameters(
            salesChannelId: 'SALES_CHANNEL_ID',
            token: '12345678912345678912345678912345',
            originalContext: $expectedContext,
        );

        $contextService = $this->createMock(SalesChannelContextService::class);
        $contextService
            ->expects(static::once())
            ->method('get')
            ->with($expectedSalesChannelContextParameters)
            ->willReturn(
                Generator::generateSalesChannelContext($expectedContext)
            );

        $resolver = new AgentRequestContextResolver(
            new JWTDecoder(),
            new RouteScopeRegistry([new AgentRouteScope()]),
            $repo,
            $contextService,
        );

        $this->expectExceptionObject(AgentException::unauthorized('Invalid JWT token'));

        $resolver->resolve($request);
    }

    public function testResolve(): void
    {
        $iat = new \DateTimeImmutable();
        $exp = new \DateTimeImmutable('+1 hour');

        $jwt = self::encodeJWT('MERCHANT_ID', $iat, $exp, ['cart', 'checkout'], 'SALES_CHANNEL_ID');

        $request = new Request();
        $request->headers->set('Authorization', $jwt);
        $request->attributes->set(PlatformRequest::ATTRIBUTE_ROUTE_SCOPE, [AgentRouteScope::ID]);
        $request->attributes->set(AgentRouteScope::ATTRIBUTE_PAYPAL_AGENT_SCOPE, ['cart', 'checkout']);

        $export = new ProductExportEntity();
        $export->setId(Uuid::randomHex());
        $export->setStorefrontSalesChannelId(Uuid::randomHex());
        $entityRepository = $this->createMock(EntityRepository::class);
        $entityRepository
            ->expects(static::once())
            ->method('search')
            ->willReturn(self::createSearchResult($export));

        $salesChannelContext = Generator::generateSalesChannelContext();

        $salesChannelMock = $this->createMock(SalesChannelContextService::class);
        $salesChannelMock
            ->expects(static::once())
            ->method('get')
            ->willReturn($salesChannelContext);

        $resolver = new AgentRequestContextResolver(
            new JWTDecoder(),
            new RouteScopeRegistry([new AgentRouteScope()]),
            $entityRepository,
            $salesChannelMock,
        );

        $resolver->resolve($request);

        static::assertTrue($request->attributes->has(PlatformRequest::ATTRIBUTE_SALES_CHANNEL_CONTEXT_OBJECT));

        $resultedSalesChannelContext = $request->attributes->get(PlatformRequest::ATTRIBUTE_SALES_CHANNEL_CONTEXT_OBJECT);

        static::assertInstanceOf(SalesChannelContext::class, $resultedSalesChannelContext);
        static::assertSame($salesChannelContext, $resultedSalesChannelContext);
    }

    /**
     * @param non-empty-string|null $sub
     * @param list<string>|null $scopes
     */
    private static function encodeJWT(?string $sub = null, ?\DateTimeImmutable $iat = null, ?\DateTimeImmutable $exp = null, ?array $scopes = null, ?string $salesChannelId = null): string
    {
        $configuration = Configuration::forAsymmetricSigner(
            new Sha512(),
            InMemory::plainText(self::JWT_PRIVATE),
            InMemory::plainText(self::JWT_PUBLIC),
        );

        $builder = $configuration->builder();

        if ($sub !== null) {
            $builder = $builder->withClaim('paypalMerchantId', $sub);
        }

        if ($scopes !== null) {
            $builder = $builder->withClaim('scope', $scopes);
        }

        if ($iat !== null) {
            $builder = $builder->issuedAt($iat);
        }

        if ($exp !== null) {
            $builder = $builder->expiresAt($exp);
        }

        if ($salesChannelId !== null) {
            $builder = $builder->withClaim('shopwareMerchantId', $salesChannelId);
        }

        return $builder
            ->getToken($configuration->signer(), $configuration->signingKey())
            ->toString();
    }

    private static function createSearchResult(?ProductExportEntity $productExport): EntitySearchResult
    {
        return new EntitySearchResult(
            ProductExportDefinition::ENTITY_NAME,
            $productExport ? 0 : 1,
            new ProductExportCollection($productExport ? [$productExport] : []),
            null,
            new Criteria(),
            Context::createDefaultContext(),
        );
    }
}
