<?php declare(strict_types=1);
/*
 * (c) shopware AG <info@shopware.com>
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Swag\PayPal\Test\RestApi\V2\Resource;

use Monolog\Level;
use PHPUnit\Framework\TestCase;
use Psr\Log\NullLogger;
use Shopware\Core\Framework\Log\Package;
use Shopware\Core\Test\TestDefaults;
use Shopware\PayPalSDK\Struct\ConstantsV2;
use Shopware\PayPalSDK\Struct\V2\Order;
use Swag\PayPal\RestApi\PartnerAttributionId;
use Swag\PayPal\RestApi\V2\Resource\OrderResource;
use Swag\PayPal\Test\Helper\PayPalLogTrait;
use Swag\PayPal\Test\Mock\PayPal\Client\_fixtures\V2\AuthorizeOrderAuthorization;
use Swag\PayPal\Test\Mock\PayPal\Client\_fixtures\V2\CaptureOrderCapture;
use Swag\PayPal\Test\Mock\PayPal\Client\_fixtures\V2\CreateOrderCapture;
use Swag\PayPal\Test\Mock\PayPal\Client\_fixtures\V2\GetCapturedOrderCapture;
use Swag\PayPal\Test\Mock\PayPal\Client\_fixtures\V2\GetOrderCapture;
use Swag\PayPal\Test\Mock\PayPal\Client\_fixtures\V2\GetRefundedOrderCapture;
use Swag\PayPal\Test\Mock\PayPalSDK\ApiContextFactoryMock;
use Swag\PayPal\Test\Mock\PayPalSDK\GatewayTestBehaviour;
use Swag\PayPal\Util\Log\LogCode;
use Swag\PayPal\Util\Log\LogKey;

/**
 * @internal
 */
#[Package('checkout')]
class OrderResourceTest extends TestCase
{
    use GatewayTestBehaviour;

    use PayPalLogTrait;

    public function testGetCreated(): void
    {
        $orderId = GetOrderCapture::ID;
        $order = $this->createResource()->get($orderId, TestDefaults::SALES_CHANNEL);

        static::assertSame($orderId, $order->getId());
        static::assertSame(ConstantsV2::INTENT_CAPTURE, $order->getIntent());
        static::assertSame('APPROVED', $order->getStatus());
    }

    public function testGetCaptured(): void
    {
        $orderId = GetCapturedOrderCapture::ID;
        $order = $this->createResource()->get($orderId, TestDefaults::SALES_CHANNEL);

        static::assertSame($orderId, $order->getId());
        static::assertSame(ConstantsV2::INTENT_CAPTURE, $order->getIntent());
        static::assertSame('COMPLETED', $order->getStatus());
    }

    public function testGetRefunded(): void
    {
        $orderId = GetRefundedOrderCapture::ID;
        $order = $this->createResource()->get($orderId, TestDefaults::SALES_CHANNEL);

        static::assertSame($orderId, $order->getId());
        static::assertSame(ConstantsV2::INTENT_CAPTURE, $order->getIntent());
        static::assertSame('COMPLETED', $order->getStatus());
    }

    public function testCapture(): void
    {
        $order = $this->createResource()->capture('orderId', TestDefaults::SALES_CHANNEL, PartnerAttributionId::PAYPAL_CLASSIC);

        static::assertSame(CaptureOrderCapture::ID, $order->getId());
        $payments = $order->getPurchaseUnits()->first()?->getPayments();
        static::assertNotNull($payments);
        $captures = $payments->getCaptures();
        static::assertNotNull($captures);
        static::assertTrue($captures->first()?->isFinalCapture());
        static::assertNull($payments->getRefunds());
        static::assertNull($payments->getAuthorizations());
    }

    public function testCreate(): void
    {
        $order = new Order();
        $order->setIntent(CreateOrderCapture::ID);

        $orderResponse = $this->createResource()->create($order, TestDefaults::SALES_CHANNEL, PartnerAttributionId::PAYPAL_CLASSIC);

        static::assertSame(CreateOrderCapture::ID, $orderResponse->getId());
    }

    public function testCreateLogsTheCreatedOrder(): void
    {
        $resource = new OrderResource(self::orderGateway(), new ApiContextFactoryMock(), $this->createPayPalLogger());

        $order = new Order();
        $order->setIntent(CreateOrderCapture::ID);
        $order->assign(['purchase_units' => [['amount' => ['value' => '10.00', 'currency_code' => 'EUR'], 'invoice_id' => 'INV-1']]]);
        $response = $resource->create($order, TestDefaults::SALES_CHANNEL, PartnerAttributionId::PAYPAL_CLASSIC);

        $this->assertPayPalLogged(LogCode::CHECKOUT_ORDER_CREATED, Level::Info, [
            LogKey::PAYPAL_ORDER_ID => $response->getId(),
            LogKey::SALES_CHANNEL_ID => TestDefaults::SALES_CHANNEL,
            LogKey::PARTNER_ATTRIBUTION_ID => PartnerAttributionId::PAYPAL_CLASSIC,
            // taken from the sent order, not the response
            LogKey::AMOUNT => '10.00',
            LogKey::CURRENCY => 'EUR',
            LogKey::INVOICE_ID => 'INV-1',
        ], 'Created PayPal order {paypalOrderId}');
    }

    public function testAuthorize(): void
    {
        $order = $this->createResource()->authorize('orderId', TestDefaults::SALES_CHANNEL, PartnerAttributionId::PAYPAL_CLASSIC);

        static::assertSame(AuthorizeOrderAuthorization::ID, $order->getId());
        $payments = $order->getPurchaseUnits()->first()?->getPayments();
        static::assertNotNull($payments);
        $authorizations = $payments->getAuthorizations();
        static::assertNotNull($authorizations);
        static::assertSame('CREATED', $authorizations->first()?->getStatus());
        static::assertNull($payments->getCaptures());
        static::assertNull($payments->getRefunds());
    }

    private function createResource(): OrderResource
    {
        return new OrderResource(self::orderGateway(), new ApiContextFactoryMock(), new NullLogger());
    }
}
