<?php declare(strict_types=1);

namespace Swag\PayPal\Test\PayPal\Resource;

use PHPUnit\Framework\TestCase;
use Shopware\Core\Defaults;
use Swag\PayPal\Payment\PayPalPaymentHandler;
use Swag\PayPal\PayPal\Api\Refund;
use Swag\PayPal\PayPal\PaymentStatus;
use Swag\PayPal\PayPal\Resource\SaleResource;
use Swag\PayPal\Test\Helper\ServicesTrait;

class SaleResourceTest extends TestCase
{
    use ServicesTrait;

    public function testRefund(): void
    {
        $refund = new Refund();
        $refundResponse = $this->createSaleResource()->refund(
            PayPalPaymentHandler::PAYPAL_REQUEST_PARAMETER_PAYMENT_ID,
            $refund,
            Defaults::SALES_CHANNEL
        );

        static::assertSame(PaymentStatus::PAYMENT_COMPLETED, $refundResponse->getState());
    }

    private function createSaleResource(): SaleResource
    {
        return new SaleResource(
            $this->createPayPalClientFactory()
        );
    }
}
