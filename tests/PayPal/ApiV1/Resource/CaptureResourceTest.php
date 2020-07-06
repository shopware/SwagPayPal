<?php declare(strict_types=1);
/*
 * (c) shopware AG <info@shopware.com>
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Swag\PayPal\Test\PayPal\ApiV1\Resource;

use PHPUnit\Framework\TestCase;
use Shopware\Core\Defaults;
use Swag\PayPal\PayPal\ApiV1\Api\Refund;
use Swag\PayPal\PayPal\ApiV1\PaymentStatusV1;
use Swag\PayPal\PayPal\ApiV1\Resource\CaptureResource;
use Swag\PayPal\Test\Helper\ServicesTrait;

class CaptureResourceTest extends TestCase
{
    use ServicesTrait;

    public function testGet(): void
    {
        $captureResponse = $this->createCaptureResource()->get(
            'captureId',
            Defaults::SALES_CHANNEL
        );

        $capture = \json_encode($captureResponse);
        static::assertNotFalse($capture);

        $captureArray = \json_decode($capture, true);

        static::assertTrue($captureArray['is_final_capture']);
    }

    public function testRefund(): void
    {
        $refund = new Refund();
        $refundResponse = $this->createCaptureResource()->refund('refundId', $refund, Defaults::SALES_CHANNEL);

        static::assertSame(PaymentStatusV1::PAYMENT_COMPLETED, $refundResponse->getState());
    }

    private function createCaptureResource(): CaptureResource
    {
        return new CaptureResource(
            $this->createPayPalClientFactory()
        );
    }
}
