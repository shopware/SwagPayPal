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
use Shopware\Core\Framework\Context;
use Shopware\Core\Framework\JWT\JWTDecoder;
use Shopware\Core\Framework\Log\Package;
use Shopware\Core\Framework\Routing\RouteScope;
use Shopware\Core\Framework\Routing\RouteScopeRegistry;
use Shopware\Core\PlatformRequest;
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
            new RouteScopeRegistry([])
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
            new RouteScopeRegistry([new AgentRouteScope(), $wrongScope])
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
            new RouteScopeRegistry([new AgentRouteScope()])
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
            ['cart', 'checkout']
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

        $resolver = new AgentRequestContextResolver(
            new JWTDecoder(),
            new RouteScopeRegistry([new AgentRouteScope()])
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
            ['cart', 'checkout']
        );

        $request = new Request();
        $request->headers->set('Authorization', $jwt);
        $request->attributes->set(PlatformRequest::ATTRIBUTE_ROUTE_SCOPE, [AgentRouteScope::ID]);
        $request->attributes->set(AgentRouteScope::ATTRIBUTE_PAYPAL_AGENT_SCOPE, ['cart', 'checkout']);

        $resolver = new AgentRequestContextResolver(
            new JWTDecoder(),
            new RouteScopeRegistry([new AgentRouteScope()])
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
            new RouteScopeRegistry([new AgentRouteScope()])
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
            $claims['sub'] ?? null,
            $claims['iat'] ?? null,
            $claims['exp'] ?? null,
            $claims['scope'] ?? null
        );

        $request = new Request();
        $request->headers->set('Authorization', $token);
        $request->attributes->set(PlatformRequest::ATTRIBUTE_ROUTE_SCOPE, [AgentRouteScope::ID]);
        $request->attributes->set(AgentRouteScope::ATTRIBUTE_PAYPAL_AGENT_SCOPE, ['cart', 'checkout']);

        $resolver = new AgentRequestContextResolver(
            new JWTDecoder(),
            new RouteScopeRegistry([new AgentRouteScope()])
        );

        $this->expectExceptionObject(AgentException::unauthorized('Invalid JWT token'));

        $resolver->resolve($request);
    }

    public static function malformedJWTProvider(): \Generator
    {
        yield 'Missing all' => [[]];

        yield 'Missing sub' => [['iat' => new \DateTimeImmutable(), 'exp' => new \DateTimeImmutable('+1 hour'), 'scope' => ['cart', 'checkout']]];
        yield 'Missing iat' => [['sub' => 'MERCHANT_ID', 'exp' => new \DateTimeImmutable('+1 hour'), 'scope' => ['cart', 'checkout']]];
        yield 'Missing exp' => [['sub' => 'MERCHANT_ID', 'iat' => new \DateTimeImmutable(), 'scope' => ['cart', 'checkout']]];
        yield 'Missing scope' => [['sub' => 'MERCHANT_ID', 'iat' => new \DateTimeImmutable(), 'exp' => new \DateTimeImmutable('+1 hour')]];

        yield 'Empty sub' => [['sub' => '', 'iat' => new \DateTimeImmutable(), 'exp' => new \DateTimeImmutable('+1 hour'), 'scope' => ['cart', 'checkout']]];
        yield 'Empty scope' => [['sub' => 'MERCHANT_ID', 'iat' => new \DateTimeImmutable(), 'exp' => new \DateTimeImmutable('+1 hour'), 'scope' => []]];
    }

    public function testResolveWithWrongAgentScopeInRoute(): void
    {
        $jwt = self::encodeJWT(
            'MERCHANT_ID',
            new \DateTimeImmutable(),
            new \DateTimeImmutable('+1 hour'),
            ['cart', 'checkout']
        );

        $request = new Request();
        $request->headers->set('Authorization', $jwt);
        $request->attributes->set(PlatformRequest::ATTRIBUTE_ROUTE_SCOPE, [AgentRouteScope::ID]);
        $request->attributes->set(AgentRouteScope::ATTRIBUTE_PAYPAL_AGENT_SCOPE, ['wrong-scope']);

        $resolver = new AgentRequestContextResolver(
            new JWTDecoder(),
            new RouteScopeRegistry([new AgentRouteScope()])
        );

        $this->expectExceptionObject(AgentException::unauthorized('Invalid JWT token'));

        $resolver->resolve($request);
    }

    public function testResolveWithWrongAgentScopeInRequest(): void
    {
        $jwt = self::encodeJWT(
            'MERCHANT_ID',
            new \DateTimeImmutable(),
            new \DateTimeImmutable('+1 hour'),
            ['these', 'are', 'wrong', 'scopes']
        );

        $request = new Request();
        $request->headers->set('Authorization', $jwt);
        $request->attributes->set(PlatformRequest::ATTRIBUTE_ROUTE_SCOPE, [AgentRouteScope::ID]);
        $request->attributes->set(AgentRouteScope::ATTRIBUTE_PAYPAL_AGENT_SCOPE, ['wrong-scope']);

        $resolver = new AgentRequestContextResolver(
            new JWTDecoder(),
            new RouteScopeRegistry([new AgentRouteScope()])
        );

        $this->expectExceptionObject(AgentException::unauthorized('Invalid JWT token'));

        $resolver->resolve($request);
    }

    public function testResolve(): void
    {
        $iat = new \DateTimeImmutable();
        $exp = new \DateTimeImmutable('+1 hour');

        $jwt = self::encodeJWT('MERCHANT_ID', $iat, $exp, ['cart', 'checkout']);

        $request = new Request();
        $request->headers->set('Authorization', $jwt);
        $request->attributes->set(PlatformRequest::ATTRIBUTE_ROUTE_SCOPE, [AgentRouteScope::ID]);
        $request->attributes->set(AgentRouteScope::ATTRIBUTE_PAYPAL_AGENT_SCOPE, ['cart', 'checkout']);

        $resolver = new AgentRequestContextResolver(
            new JWTDecoder(),
            new RouteScopeRegistry([new AgentRouteScope()])
        );

        $resolver->resolve($request);

        static::assertTrue($request->attributes->has(PlatformRequest::ATTRIBUTE_CONTEXT_OBJECT));

        $context = $request->attributes->get(PlatformRequest::ATTRIBUTE_CONTEXT_OBJECT);

        static::assertInstanceOf(Context::class, $context);

        $source = $context->getSource();

        static::assertInstanceOf(AgentSource::class, $source);

        static::assertSame('MERCHANT_ID', $source->merchantId);
        static::assertTrue($source->hasScope('cart'));
        static::assertTrue($source->hasScope('checkout'));
        static::assertFalse($source->hasScope('wrong-scope'));

        static::assertEquals($iat, $source->issuedAt);
        static::assertEquals($exp, $source->expiresAt);
        static::assertFalse($source->isExpired());
    }

    /**
     * @param non-empty-string|null $sub
     * @param list<string>|null $scopes
     */
    private static function encodeJWT(?string $sub = null, ?\DateTimeImmutable $iat = null, ?\DateTimeImmutable $exp = null, ?array $scopes = null): string
    {
        $configuration = Configuration::forAsymmetricSigner(
            new Sha512(),
            InMemory::plainText(self::JWT_PRIVATE),
            InMemory::plainText(self::JWT_PUBLIC),
        );

        $builder = $configuration->builder();

        if ($sub !== null) {
            $builder = $builder->relatedTo($sub);
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

        return $builder
            ->getToken($configuration->signer(), $configuration->signingKey())
            ->toString();
    }
}
