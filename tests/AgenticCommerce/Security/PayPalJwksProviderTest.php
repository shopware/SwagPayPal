<?php declare(strict_types=1);
/*
 * (c) shopware AG <info@shopware.com>
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Swag\PayPal\Tests\AgenticCommerce\Security;

use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;
use Shopware\Core\Framework\JWT\JWTException;
use Shopware\Core\Framework\Log\Package;
use Swag\PayPal\AgenticCommerce\Security\PayPalJwksProvider;
use Symfony\Component\Cache\Adapter\ArrayAdapter;
use Symfony\Component\HttpClient\MockHttpClient;
use Symfony\Component\HttpClient\Response\MockResponse;

/**
 * @internal
 */
#[Package('checkout')]
#[CoversClass(PayPalJwksProvider::class)]
class PayPalJwksProviderTest extends TestCase
{
    private const JWKS = <<<'JSON'
        {
            "keys": [
                {
                    "kty": "RSA",
                    "n": "vv7Pi1nWWrJj4n5-6gX9B7BQpctaPEg9VdVK1kzc9xBNwZobeWEgEmiUGtkrn8S5R6Q4NmB4hnb8F5jeCX5OkyA49mgzw4wNXUPGTGMY5Eoxt9zu1Heaivkljh4-wN6d01oIFkHT6E7VjEJOG2RA49t7fgQ1phJIUK39B0RAXIG2pYicbujeiiJ12iQipMjY_TVD0KZgUc2Vj2apk7Dv1YBqFG-HlSG5hWu880IzGQE9Pds5qekIawJJyed08otq29hDHlFd28B0fFhdzcu8cN83NxddXBlh77b8-a7gaWC5_Iw45THRpIsiG41uX0r0INEDcnR3qCUkz6m9LOVWkQ",
                    "e": "AQAB",
                    "use": "sig",
                    "alg": "RS256",
                    "kid": "5874bc103b80920f"
                }
            ]
        }
        JSON;

    public function testGetJwksFetchesAndCachesResponse(): void
    {
        $requests = 0;

        $provider = new PayPalJwksProvider(
            new MockHttpClient(static function (string $method, string $url) use (&$requests): MockResponse {
                ++$requests;

                static::assertSame('GET', $method);
                static::assertSame('https://www.paypal.ai/.well-known/jwks.json', $url);

                return new MockResponse(self::JWKS);
            }),
            new ArrayAdapter(),
        );

        $jwks = $provider->getJwks();
        $cachedJwks = $provider->getJwks();

        static::assertSame(1, $requests);
        static::assertSame('5874bc103b80920f', \array_values($jwks->getElements())[0]->kid);
        static::assertSame('5874bc103b80920f', \array_values($cachedJwks->getElements())[0]->kid);
    }

    public function testGetJwksRefreshesCache(): void
    {
        $responses = [
            self::JWKS,
            \str_replace('5874bc103b80920f', 'refreshed-key', self::JWKS),
        ];

        $provider = new PayPalJwksProvider(
            new MockHttpClient(static function () use (&$responses): MockResponse {
                $response = \array_shift($responses);
                static::assertIsString($response);

                return new MockResponse($response);
            }),
            new ArrayAdapter(),
        );

        $provider->getJwks();
        $jwks = $provider->getJwks(true);

        static::assertSame('refreshed-key', \array_values($jwks->getElements())[0]->kid);
        static::assertSame([], $responses);
    }

    public function testGetJwksThrowsOnInvalidJson(): void
    {
        $provider = new PayPalJwksProvider(
            new MockHttpClient(new MockResponse('invalid-json')),
            new ArrayAdapter(),
        );

        $this->expectException(JWTException::class);
        $this->expectExceptionMessage('Invalid JWK: PayPal JWKS response is invalid JSON');

        $provider->getJwks();
    }

    public function testGetJwksThrowsOnHttpError(): void
    {
        $provider = new PayPalJwksProvider(
            new MockHttpClient(new MockResponse('', ['http_code' => 500])),
            new ArrayAdapter(),
        );

        $this->expectException(JWTException::class);
        $this->expectExceptionMessage('Invalid JWK: Could not fetch PayPal JWKS. Status code: 500');

        $provider->getJwks();
    }
}
