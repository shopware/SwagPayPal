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
use Shopware\Core\Framework\JWT\JWTException;
use Shopware\Core\Framework\Log\Package;
use Shopware\Core\Framework\Routing\RequestContextResolverInterface;
use Shopware\Core\Framework\Routing\RouteScopeCheckTrait;
use Shopware\Core\Framework\Routing\RouteScopeRegistry;
use Shopware\Core\PlatformRequest;
use Swag\PayPal\AgentCommerce\Exception\AgentException;
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

        if (!$this->isRequestScoped($request, AgentContextRouteScopeDependant::class)) {
            return;
        }

        $token = $request->headers->get('Authorization');

        if (!$token) {
            throw AgentException::unauthorized('Missing Authorization header');
        }

        $this->validateJWT($token);
        $source = $this->resolveContextSource($token);

        $this->validateAgentScopes($request, $source);

        $context = new Context($source);

        $request->attributes->set(PlatformRequest::ATTRIBUTE_CONTEXT_OBJECT, $context);
    }

    protected function getScopeRegistry(): RouteScopeRegistry
    {
        return $this->routeScopeRegistry;
    }

    private function validateJWT(string $jwt): void
    {
        $constraints = [
            new SignedWith(new Sha512(), InMemory::plainText(self::$PAYPAL_JWT)),
        ];

        try {
            $this->JWTDecoder->validate($jwt, ...$constraints);
        } catch (JWTException) {
            throw AgentException::unauthorized('Invalid JWT token');
        }
    }

    private function resolveContextSource(string $token): AgentSource
    {
        $decoded = $this->JWTDecoder->decode($token);

        if (!isset($decoded['sub'], $decoded['iat'], $decoded['exp'], $decoded['scope'])) {
            throw AgentException::unauthorized('Invalid JWT token');
        }

        if (!\is_string($decoded['sub']) || empty($decoded['sub']) || !\is_array($decoded['scope']) || empty($decoded['scope'])) {
            throw AgentException::unauthorized('Invalid JWT token');
        }

        $iat = $decoded['iat'];
        $exp = $decoded['exp'];

        if (!$iat instanceof \DateTimeInterface || !$exp instanceof \DateTimeInterface) {
            throw AgentException::unauthorized('Invalid JWT token');
        }

        return new AgentSource($decoded['sub'], $iat, $exp, $decoded['scope']);
    }

    private function validateAgentScopes(Request $request, AgentSource $source): void
    {
        /** @var list<string> $scopes */
        $scopes = $request->attributes->get(AgentRouteScope::ATTRIBUTE_PAYPAL_AGENT_SCOPE, []);

        if ($scopes === []) {
            throw AgentException::unauthorized('Invalid JWT token');
        }

        foreach ($scopes as $scope) {
            if (!$source->hasScope($scope)) {
                throw AgentException::unauthorized('Invalid JWT token');
            }
        }
    }
}
