<?php declare(strict_types=1);
/**
 * (c) shopware AG <info@shopware.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Swag\PayPal\Test\PayPal\Resource;

use PHPUnit\Framework\TestCase;
use Shopware\Core\Framework\Context;
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
        $context = Context::createDefaultContext();
        $refundResponse = $this->createCaptureResource()->refund('refundId', $refund, $context);

        static::assertSame(PaymentStatus::PAYMENT_COMPLETED, $refundResponse->getState());
    }

    private function createCaptureResource(): CaptureResource
    {
        return new CaptureResource(
            $this->createPayPalClientFactory()
        );
    }
}
