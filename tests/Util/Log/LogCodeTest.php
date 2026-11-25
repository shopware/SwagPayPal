<?php declare(strict_types=1);
/*
 * (c) shopware AG <info@shopware.com>
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Swag\PayPal\Test\Util\Log;

use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;
use Shopware\Core\Checkout\Payment\PaymentException;
use Shopware\Core\Framework\Log\Package;
use Swag\PayPal\RestApi\Exception\PayPalApiException;
use Swag\PayPal\Storefront\BrowserErrorCode;
use Swag\PayPal\Util\Log\LogCode;
use Swag\PayPal\Webhook\Exception\WebhookOrderTransactionInvalidIdException;

/**
 * @internal
 */
#[Package('checkout')]
#[CoversClass(LogCode::class)]
class LogCodeTest extends TestCase
{
    public function testValuesFollowTheConstantNamesUnlessLinkedToAnError(): void
    {
        $linked = [
            'CHECKOUT_PAYMENT_CANCELLED' => PaymentException::PAYMENT_CUSTOMER_CANCELED_EXTERNAL,
            'CHECKOUT_INVOICE_ID_DUPLICATE' => PayPalApiException::ERROR_CODE_PREFIX . PayPalApiException::ISSUE_DUPLICATE_INVOICE_ID,
            'WEBHOOK_EVENT_UNRELATED' => WebhookOrderTransactionInvalidIdException::ERROR_CODE,
            'STOREFRONT_BUYER_CANCELLED' => BrowserErrorCode::USER_CANCELLED,
            'STOREFRONT_PAYMENT_DECLINED' => BrowserErrorCode::DECLINED,
            'STOREFRONT_SCRIPT_FAILED' => BrowserErrorCode::SCRIPT,
        ];

        $constants = (new \ReflectionClass(LogCode::class))->getConstants();

        static::assertGreaterThan(0, \count($constants));
        foreach ($constants as $name => $value) {
            static::assertSame($linked[$name] ?? $name, $value);
        }
    }

    public function testValuesAreUnique(): void
    {
        $values = \array_values((new \ReflectionClass(LogCode::class))->getConstants());

        static::assertSame($values, \array_unique($values));
    }

    public function testNoServiceUsesTheGenericLogger(): void
    {
        $files = \glob(__DIR__ . '/../../../src/Resources/config/services/{*.xml,*/*.xml}', \GLOB_BRACE);

        static::assertIsArray($files);
        static::assertGreaterThan(0, \count($files));
        foreach ($files as $file) {
            static::assertDoesNotMatchRegularExpression('/id=[\'"]logger[\'"]/', (string) \file_get_contents($file), $file . ' must use monolog.logger.paypal');
        }
    }
}
