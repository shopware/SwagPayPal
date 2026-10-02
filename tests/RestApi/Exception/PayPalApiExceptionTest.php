<?php declare(strict_types=1);
/*
 * (c) shopware AG <info@shopware.com>
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Swag\PayPal\Test\RestApi\Exception;

use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;
use Shopware\Core\Framework\Log\Package;
use Shopware\PayPalSDK\Exception\RetryAfterApiException;
use Shopware\PayPalSDK\Struct\Error\DetailCollection;
use Swag\PayPal\RestApi\Exception\PayPalApiException;

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
}
