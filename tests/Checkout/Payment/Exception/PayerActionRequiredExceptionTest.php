<?php declare(strict_types=1);
/*
 * (c) shopware AG <info@shopware.com>
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Swag\PayPal\Test\Checkout\Payment\Exception;

use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;
use Shopware\Core\Framework\Log\Package;
use Shopware\PayPalSDK\Exception\ApiException;
use Swag\PayPal\Checkout\Payment\Exception\PayerActionRequiredException;
use Symfony\Component\HttpFoundation\Response;

/**
 * @internal
 */
#[Package('checkout')]
#[CoversClass(PayerActionRequiredException::class)]
class PayerActionRequiredExceptionTest extends TestCase
{
    public function testPayerActionRequiredIdentifiesThePaymentError(): void
    {
        $exception = PayerActionRequiredException::payerActionRequired('paypalOrderId');

        static::assertSame(Response::HTTP_UNPROCESSABLE_ENTITY, $exception->getStatusCode());
        static::assertSame(ApiException::CODE_UNPROCESSABLE_ENTITY, $exception->getName());
        static::assertSame('PAYER_ACTION_REQUIRED', $exception->getIssue());
        static::assertSame('SWAG_PAYPAL__API_PAYER_ACTION_REQUIRED', $exception->getErrorCode());
        static::assertStringContainsString('"paypalOrderId"', $exception->getMessage());
        static::assertNull($exception->getPrevious());
    }

    public function testPayerActionRequiredKeepsTheOriginalException(): void
    {
        $previous = new \RuntimeException('PayPal capture failed.');

        $exception = PayerActionRequiredException::payerActionRequired('paypalOrderId', $previous);

        static::assertSame($previous, $exception->getPrevious());
    }
}
