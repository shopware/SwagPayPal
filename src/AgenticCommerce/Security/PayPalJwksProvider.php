<?php declare(strict_types=1);
/*
 * (c) shopware AG <info@shopware.com>
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Swag\PayPal\AgenticCommerce\Security;

use Shopware\Core\Framework\JWT\JWTException;
use Shopware\Core\Framework\JWT\Struct\JWKCollection;
use Shopware\Core\Framework\JWT\Struct\JWKStruct;
use Shopware\Core\Framework\Log\Package;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Contracts\Cache\CacheInterface;
use Symfony\Contracts\Cache\ItemInterface;
use Symfony\Contracts\HttpClient\Exception\ExceptionInterface;
use Symfony\Contracts\HttpClient\HttpClientInterface;

/**
 * @internal
 *
 * @phpstan-import-type JSONWebKey from JWKStruct
 */
#[Package('checkout')]
final class PayPalJwksProvider extends AbstractPayPalJwksProvider
{
    private const CACHE_KEY = 'swag_paypal.agentic_commerce.jwks';
    private const CACHE_TTL = 3600;
    private const JWKS_URL = 'https://www.paypal.ai/.well-known/jwks.json';

    public function __construct(
        private readonly HttpClientInterface $httpClient,
        private readonly CacheInterface $cache,
    ) {
    }

    public function getJwks(bool $refresh = false): JWKCollection
    {
        if ($refresh) {
            $this->cache->delete(self::CACHE_KEY);
        }

        $jwks = $this->cache->get(self::CACHE_KEY, function (ItemInterface $item): JWKCollection {
            $item->expiresAfter(self::CACHE_TTL);

            return $this->decodeJwks($this->fetchJwks());
        });

        if (!$jwks instanceof JWKCollection) {
            throw JWTException::invalidJwk('PayPal JWKS cache entry is invalid');
        }

        return $jwks;
    }

    private function fetchJwks(): string
    {
        try {
            $response = $this->httpClient->request('GET', self::JWKS_URL, [
                'headers' => [
                    'Accept' => 'application/json',
                ],
            ]);

            if ($response->getStatusCode() !== Response::HTTP_OK) {
                throw JWTException::invalidJwk(\sprintf('Could not fetch PayPal JWKS. Status code: %d', $response->getStatusCode()));
            }

            return $response->getContent();
        } catch (ExceptionInterface $e) {
            throw JWTException::invalidJwk('Could not fetch PayPal JWKS', $e instanceof \Exception ? $e : null);
        }
    }

    private function decodeJwks(string $jwks): JWKCollection
    {
        try {
            $decoded = \json_decode($jwks, true, 512, \JSON_THROW_ON_ERROR);
        } catch (\JsonException $e) {
            throw JWTException::invalidJwk('PayPal JWKS response is invalid JSON', $e);
        }

        if (!\is_array($decoded) || !isset($decoded['keys']) || !\is_array($decoded['keys'])) {
            throw JWTException::invalidJwk('PayPal JWKS response does not contain keys');
        }

        /** @var array{keys: array<int, JSONWebKey>} $decoded */
        return JWKCollection::fromArray($decoded);
    }
}
