<?php declare(strict_types=1);

namespace Swag\PayPal\Test\PayPal\Resource;

use PHPUnit\Framework\TestCase;
use Shopware\Core\Defaults;
use Swag\PayPal\PayPal\Api\Refund;
use Swag\PayPal\PayPal\PaymentStatus;
use Swag\PayPal\PayPal\Resource\CaptureResource;
use Swag\PayPal\Test\Helper\ServicesTrait;

class CaptureResourceTest extends TestCase
{
    use ServicesTrait;

    public function testRefund(): void
    {
        $refund = new Refund();
        $refundResponse = $this->createCaptureResource()->refund('refundId', $refund, Defaults::SALES_CHANNEL);

        static::assertSame(PaymentStatus::PAYMENT_COMPLETED, $refundResponse->getState());
    }

    private function createCaptureResource(): CaptureResource
    {
        return new CaptureResource(
            $this->createPayPalClientFactory()
        );
    }
}
