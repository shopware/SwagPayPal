<?php declare(strict_types=1);
/*
 * (c) shopware AG <info@shopware.com>
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Swag\PayPal\Test\RestApi\V1\Resource;

use PHPUnit\Framework\TestCase;
use Psr\Log\NullLogger;
use Shopware\Core\Framework\Log\Package;
use Shopware\Core\Test\TestDefaults;
use Swag\PayPal\RestApi\V1\Resource\OrdersResource;
use Swag\PayPal\Test\Mock\PayPal\Client\_fixtures\V1\GetResourceOrderResponseFixture;
use Swag\PayPal\Test\Mock\PayPal\Client\PayPalClientFactoryMock;

/**
 * @internal
 */
#[Package('checkout')]
class OrdersResourceTest extends TestCase
{
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

    private function createOrdersResource(): OrdersResource
    {
        return new OrdersResource(new PayPalClientFactoryMock(new NullLogger()));
    }
}
