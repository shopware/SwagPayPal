<?php declare(strict_types=1);
/*
 * (c) shopware AG <info@shopware.com>
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Swag\PayPal\AgentCommerce\Routing;

use Lcobucci\JWT\Signer\Key\InMemory;
use Lcobucci\JWT\Signer\Rsa\Sha512;
use Lcobucci\JWT\Validation\Constraint\SignedWith;
use Shopware\Core\Framework\Context;
use Shopware\Core\Framework\JWT\JWTDecoder;
use Shopware\Core\Framework\Log\Package;
use Shopware\Core\Framework\Routing\RequestContextResolverInterface;
use Shopware\Core\Framework\Routing\RouteScopeCheckTrait;
use Shopware\Core\Framework\Routing\RouteScopeRegistry;
use Shopware\Core\PlatformRequest;
use Swag\PayPal\AgentCommerce\Exception\PayPalAgentException;
use Symfony\Component\HttpFoundation\Request;

#[Package('checkout')]
class PayPalAgentRequestContextResolver implements RequestContextResolverInterface
{
    use RouteScopeCheckTrait;
    private const PAYPAL_JWT = '-----BEGIN PUBLIC KEY-----
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
     */
    public function __construct(
        private readonly JWTDecoder $JWTDecoder,
        private readonly RouteScopeRegistry $routeScopeRegistry,
    ) {
    }

    public function resolve(Request $request): void
    {
        if ($request->attributes->has(PlatformRequest::ATTRIBUTE_CONTEXT_OBJECT)) {
            return;
        }

        if (!$this->isRequestScoped($request, PayPalAgentContextRouteScopeDependant::class)) {
            return;
        }

        $token = $request->headers->get('Authorization');

        if (!$token) {
            throw PayPalAgentException::unauthorized('Missing Authorization header');
        }

        $this->validateJWT($token);
        $context = new Context($this->resolveContextSource($token));

        $request->attributes->set(PlatformRequest::ATTRIBUTE_CONTEXT_OBJECT, $context);
    }

    protected function getScopeRegistry(): RouteScopeRegistry
    {
        return $this->routeScopeRegistry;
    }

    private function validateJWT(string $jwt): void
    {
        $constraints = [
            new SignedWith(new Sha512(), InMemory::plainText(self::PAYPAL_JWT)),
        ];

        $this->JWTDecoder->validate($jwt, ...$constraints);
    }

    private function resolveContextSource(string $token): PayPalAgentSource
    {
        $decoded = $this->JWTDecoder->decode($token);

        if (!isset($decoded['sub'], $decoded['iat'], $decoded['exp'], $decoded['scope'])) {
            throw PayPalAgentException::unauthorized('Invalid JWT token');
        }

        if (!\is_string($decoded['sub']) || !\is_array($decoded['scope'])) {
            throw PayPalAgentException::unauthorized('Invalid JWT token');
        }

        $iat = $decoded['iat'];
        $exp = $decoded['exp'];

        if (!$iat instanceof \DateTimeInterface || !$exp instanceof \DateTimeInterface) {
            throw PayPalAgentException::unauthorized('Invalid JWT token');
        }

        return new PayPalAgentSource($decoded['sub'], $iat, $exp, $decoded['scope']);
    }
}
