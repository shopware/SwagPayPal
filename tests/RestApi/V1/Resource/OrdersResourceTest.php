<?php declare(strict_types=1);
/*
 * (c) shopware AG <info@shopware.com>
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Swag\PayPal\Test\RestApi\V1\Resource;

use PHPUnit\Framework\TestCase;
use Shopware\Core\Test\TestDefaults;
use Swag\PayPal\RestApi\V1\Api\Capture;
use Swag\PayPal\RestApi\V1\Resource\OrdersResource;
use Swag\PayPal\Test\Helper\ServicesTrait;
use Swag\PayPal\Test\Mock\PayPal\Client\_fixtures\V1\GetResourceOrderResponseFixture;
use Swag\PayPal\Test\Mock\PayPal\Client\_fixtures\V1\VoidOrderResponseFixture;

class OrdersResourceTest extends TestCase
{
    use ServicesTrait;

    public function testGet(): void
    {
        $ordersResponse = $this->createOrdersResource()->get(
            'ordersId',
            TestDefaults::SALES_CHANNEL
        );

        $orders = \json_encode($ordersResponse);
        static::assertNotFalse($orders);

        $ordersArray = \json_decode($orders, true);

        static::assertSame(GetResourceOrderResponseFixture::ID, $ordersArray['id']);
    }

    public function testCapture(): void
    {
        $capture = new Capture();
        $captureResponse = $this->createOrdersResource()->capture('captureId', $capture, TestDefaults::SALES_CHANNEL);

        $capture = \json_encode($captureResponse);
        static::assertNotFalse($capture);

        $captureArray = \json_decode($capture, true);

        static::assertTrue($captureArray['is_final_capture']);
    }

    public function testVoid(): void
    {
        $voidResponse = $this->createOrdersResource()->void('voidId', TestDefaults::SALES_CHANNEL);

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
