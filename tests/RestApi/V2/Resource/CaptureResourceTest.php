<?php declare(strict_types=1);
/*
 * (c) shopware AG <info@shopware.com>
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Swag\PayPal\Test\RestApi\V2\Resource;

use PHPUnit\Framework\TestCase;
use Shopware\Core\Defaults;
use Swag\PayPal\RestApi\V2\Api\Order\PurchaseUnit\Payments\Refund;
use Swag\PayPal\RestApi\V2\Resource\CaptureResource;
use Swag\PayPal\Test\Helper\ServicesTrait;
use Swag\PayPal\Test\Mock\PayPal\Client\_fixtures\V2\GetCapture;
use Swag\PayPal\Test\Mock\PayPal\Client\_fixtures\V2\RefundCapture;

class CaptureResourceTest extends TestCase
{
    use ServicesTrait;

    public function testGet(): void
    {
        $captureId = GetCapture::ID;
        $capture = $this->createResource()->get($captureId, Defaults::SALES_CHANNEL);

        static::assertSame($captureId, $capture->getId());
        static::assertFalse($capture->isFinalCapture());
    }

    public function testRefund(): void
    {
        $refund = new Refund();
        $refund = $this->createResource()->refund('captureId', $refund, Defaults::SALES_CHANNEL, false);

        static::assertSame(RefundCapture::TOTAL_REFUNDED_AMOUNT_VALUE, $refund->getSellerPayableBreakdown()->getTotalRefundedAmount()->getValue());
    }

    private function createResource(): CaptureResource
    {
        return new CaptureResource($this->createPayPalClientFactory());
    }
}
