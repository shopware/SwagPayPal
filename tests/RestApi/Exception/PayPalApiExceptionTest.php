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
use Shopware\PayPalSDK\Exception\ErrorApiException;
use Shopware\PayPalSDK\Exception\RetryAfterApiException;
use Shopware\PayPalSDK\Struct\Error\Detail;
use Shopware\PayPalSDK\Struct\Error\DetailCollection;
use Shopware\PayPalSDK\Struct\V1\Common\Link as V1Link;
use Shopware\PayPalSDK\Struct\V1\Common\LinkCollection;
use Shopware\PayPalSDK\Struct\V2\Common\Link;
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

    /**
     * Pins the detection contract: the last detail issue becomes the issue and derives the snippet key.
     */
    public function testFromExtractsPayerActionRequiredIssue(): void
    {
        $exception = PayPalApiException::from(new class extends ErrorApiException {
            public function __construct()
            {
            }

            public function getErrorCode(): string
            {
                return 'UNPROCESSABLE_ENTITY';
            }

            public function getReason(): string
            {
                return 'The requested action could not be performed, semantically incorrect, or failed business validation.';
            }

            public function getStatusCode(): int
            {
                return Response::HTTP_UNPROCESSABLE_ENTITY;
            }

            public function getDetails(): DetailCollection
            {
                return new DetailCollection([
                    (new Detail())->assign([
                        'issue' => 'PAYER_ACTION_REQUIRED',
                        'description' => 'Payer needs to perform the following action before proceeding with payment.',
                    ]),
                ]);
            }

            public function getLinks(): LinkCollection
            {
                return new LinkCollection([
                    (new V1Link())->assign([
                        'rel' => Link::RELATION_PAYER_ACTION,
                        'href' => 'https://www.sandbox.paypal.com/checkoutnow?token=ORDER-ID',
                        'method' => 'GET',
                    ]),
                ]);
            }
        });

        static::assertSame('PAYER_ACTION_REQUIRED', $exception->getIssue());
        static::assertTrue($exception->is('PAYER_ACTION_REQUIRED'));
        static::assertSame('SWAG_PAYPAL__API_PAYER_ACTION_REQUIRED', $exception->getErrorCode());

        // PayPal returns the renewed approval link on the failing response only
        $error = $exception->getPrevious();
        static::assertInstanceOf(ErrorApiException::class, $error);
        $payerAction = $error->getLinks()->first();
        static::assertNotNull($payerAction);
        static::assertSame(Link::RELATION_PAYER_ACTION, $payerAction->getRel());
        static::assertSame('https://www.sandbox.paypal.com/checkoutnow?token=ORDER-ID', $payerAction->getHref());
    }
}
