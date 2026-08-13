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
use Swag\PayPal\RestApi\Exception\PayPalApiException;
use Swag\PayPal\RestApi\RequestService;

/**
 * @internal
 */
#[Package('checkout')]
#[CoversClass(RequestService::class)]
class RequestServiceTest extends TestCase
{
    public function testHandleResponseWrapsSdkApiException(): void
    {
        $response = new Response(
            400,
            [],
            \json_encode([
                'name' => 'INVALID_REQUEST',
                'message' => 'Invalid request',
            ], \JSON_THROW_ON_ERROR),
        );

        $requestService = new RequestService();

        try {
            $requestService->handleResponse($response);
            static::fail('Expected PayPalApiException was not thrown.');
        } catch (PayPalApiException $e) {
            static::assertTrue($e->is('INVALID_REQUEST'));
            static::assertNull($e->getRetryAt());
        }
    }
}
