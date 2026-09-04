<?php declare(strict_types=1);
/*
 * (c) shopware AG <info@shopware.com>
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Swag\PayPal\Test\RestApi\Exception;

use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;
use Psr\Http\Client\NetworkExceptionInterface;
use Psr\Http\Message\RequestInterface;
use Shopware\Core\Framework\Log\Package;
use Shopware\PayPalSDK\Exception\RetryAfterApiException;
use Shopware\PayPalSDK\Struct\Error\DetailCollection;
use Swag\PayPal\RestApi\Exception\PayPalApiException;
use Symfony\Component\HttpFoundation\Response;

/**
 * @internal
 */
#[Package('checkout')]
#[CoversClass(PayPalApiException::class)]
class PayPalApiExceptionTest extends TestCase
{
    public function testFromKeepsRetryAtFromSdkException(): void
    {
        $retryAt = new \DateTimeImmutable('2026-01-01T00:02:00+00:00');

        $exception = PayPalApiException::from(new class($retryAt) extends RetryAfterApiException {
            public function __construct(private readonly \DateTimeImmutable $retryAt)
            {
            }

            public function getErrorCode(): string
            {
                return 'RATE_LIMIT_REACHED';
            }

            public function getReason(): string
            {
                return 'Rate limit reached';
            }

            public function getStatusCode(): int
            {
                return 429;
            }

            public function getDetails(): DetailCollection
            {
                return new DetailCollection();
            }

            public function getRetryAt(): \DateTimeImmutable
            {
                return $this->retryAt;
            }
        });

        static::assertTrue($exception->is('RATE_LIMIT_REACHED'));
        static::assertSame($retryAt, $exception->getRetryAt());
    }

    public function testFromClientException(): void
    {
        $request = $this->createMock(RequestInterface::class);

        $networkException = new class($request) extends \RuntimeException implements NetworkExceptionInterface {
            public function __construct(private readonly RequestInterface $request)
            {
                parent::__construct('Could not resolve host: api-m.paypal.com');
            }

            public function getRequest(): RequestInterface
            {
                return $this->request;
            }
        };

        $exception = PayPalApiException::fromClientException($networkException);

        static::assertSame(Response::HTTP_BAD_GATEWAY, $exception->getStatusCode());
        static::assertTrue($exception->is(PayPalApiException::ISSUE_NETWORK_ERROR));
        static::assertTrue($exception->is('SERVICE_UNAVAILABLE'));
        static::assertSame('SWAG_PAYPAL__API_NETWORK_ERROR', $exception->getErrorCode());
        static::assertStringContainsString('PayPal is currently unreachable', $exception->getMessage());
        static::assertNull($exception->getRetryAt());
    }
}
