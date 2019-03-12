<?php declare(strict_types=1);
/**
 * (c) shopware AG <info@shopware.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace SwagPayPal\Test\PayPal\Resource;

use PHPUnit\Framework\TestCase;
use Shopware\Core\Framework\Context;
use SwagPayPal\PayPal\Api\Refund;
use SwagPayPal\PayPal\PaymentStatus;
use SwagPayPal\PayPal\Resource\CaptureResource;
use SwagPayPal\Test\Helper\ServicesTrait;

class CaptureResourceTest extends TestCase
{
    use ServicesTrait;

    public function testRefund(): void
    {
        $resource = $this->createCaptureResource();

        $refund = new Refund();
        $context = Context::createDefaultContext();
        $refundResponse = $resource->refund('paymentId', $refund, $context);

        static::assertSame(PaymentStatus::PAYMENT_COMPLETED, $refundResponse->getState());
    }

    private function createCaptureResource(): CaptureResource
    {
        return new CaptureResource(
            $this->createPayPalClientFactory()
        );
    }
}
