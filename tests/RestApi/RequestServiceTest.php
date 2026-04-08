<?php declare(strict_types=1);
/*
 * (c) shopware AG <info@shopware.com>
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Swag\PayPal\Test\RestApi;

use GuzzleHttp\Psr7\Response;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;
use Shopware\Core\Framework\Log\Package;
use Shopware\PayPalSDK\Exception\ApiException;
use Swag\PayPal\RestApi\Exception\PayPalApiException;
use Swag\PayPal\RestApi\RequestService;

/**
 * @internal
 */
#[Package('checkout')]
#[CoversClass(RequestService::class)]
class RequestServiceTest extends TestCase
{
    public function testHandleResponseAddsRetryDelayFromRetryAfterHeader(): void
    {
        $response = new Response(
            429,
            ['Retry-After' => '120'],
            \json_encode([
                'name' => 'RATE_LIMIT_REACHED',
                'message' => 'Rate limit reached',
            ], \JSON_THROW_ON_ERROR),
        );

        $requestService = new RequestService();

        try {
            $requestService->handleResponse($response);
            static::fail('Expected PayPalApiException was not thrown.');
        } catch (PayPalApiException $e) {
            static::assertTrue($e->is(ApiException::CODE_RATE_LIMIT_REACHED));
            static::assertNotNull($e->getRetryAt());
            static::assertNotNull($e->getRetryDelay());
            static::assertGreaterThanOrEqual(110000, $e->getRetryDelay());
            static::assertLessThanOrEqual(120000, $e->getRetryDelay());
        }
    }

    public function testHandleResponseAddsRetryDelayFromRetryAfterDateHeader(): void
    {
        $retryAt = new \DateTimeImmutable('+2 minutes');
        $response = new Response(
            429,
            ['Retry-After' => $retryAt->format(\DateTimeInterface::RFC7231)],
            \json_encode([
                'name' => 'RATE_LIMIT_REACHED',
                'message' => 'Rate limit reached',
            ], \JSON_THROW_ON_ERROR),
        );

        $requestService = new RequestService();

        try {
            $requestService->handleResponse($response);
            static::fail('Expected PayPalApiException was not thrown.');
        } catch (PayPalApiException $e) {
            static::assertTrue($e->is(ApiException::CODE_RATE_LIMIT_REACHED));
            static::assertNotNull($e->getRetryAt());
            static::assertNotNull($e->getRetryDelay());
            static::assertGreaterThanOrEqual(110000, $e->getRetryDelay());
            static::assertLessThanOrEqual(120000, $e->getRetryDelay());
        }
    }
}
