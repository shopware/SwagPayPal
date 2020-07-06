<?php declare(strict_types=1);
/*
 * (c) shopware AG <info@shopware.com>
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Swag\PayPal\Test\PayPal\ApiV1\Resource;

use PHPUnit\Framework\TestCase;
use Shopware\Core\Defaults;
use Swag\PayPal\PayPal\ApiV1\Api\Capture;
use Swag\PayPal\PayPal\ApiV1\Resource\OrdersResource;
use Swag\PayPal\Test\Helper\ServicesTrait;
use Swag\PayPal\Test\Mock\PayPal\Client\_fixtures\GetResourceOrderResponseFixture;
use Swag\PayPal\Test\Mock\PayPal\Client\_fixtures\VoidOrderResponseFixture;

class OrdersResourceTest extends TestCase
{
    use ServicesTrait;

    public function testGet(): void
    {
        $ordersResponse = $this->createOrdersResource()->get(
            'ordersId',
            Defaults::SALES_CHANNEL
        );

        $orders = \json_encode($ordersResponse);
        static::assertNotFalse($orders);

        $ordersArray = \json_decode($orders, true);

        static::assertSame(GetResourceOrderResponseFixture::ID, $ordersArray['id']);
    }

    public function testCapture(): void
    {
        $capture = new Capture();
        $captureResponse = $this->createOrdersResource()->capture('captureId', $capture, Defaults::SALES_CHANNEL);

        $capture = \json_encode($captureResponse);
        static::assertNotFalse($capture);

        $captureArray = \json_decode($capture, true);

        static::assertTrue($captureArray['is_final_capture']);
    }

    public function testVoid(): void
    {
        $voidResponse = $this->createOrdersResource()->void('voidId', Defaults::SALES_CHANNEL);

        $void = \json_encode($voidResponse);
        static::assertNotFalse($void);

        $voidArray = \json_decode($void, true);

        static::assertSame(VoidOrderResponseFixture::VOID_ID, $voidArray['id']);
    }

    private function createOrdersResource(): OrdersResource
    {
        return new OrdersResource(
            $this->createPayPalClientFactory()
        );
    }
}
