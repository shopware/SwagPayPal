<?php declare(strict_types=1);
/*
 * (c) shopware AG <info@shopware.com>
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Swag\PayPal\Test\RestApi\V2\Resource;

use PHPUnit\Framework\TestCase;
use Shopware\Core\Checkout\Payment\PaymentMethodCollection;
use Shopware\Core\Defaults;
use Shopware\Core\Framework\Test\TestCaseBase\BasicTestDataBehaviour;
use Shopware\Core\Framework\Test\TestCaseBase\DatabaseTransactionBehaviour;
use Swag\PayPal\RestApi\PartnerAttributionId;
use Swag\PayPal\RestApi\V2\PaymentIntentV2;
use Swag\PayPal\RestApi\V2\Resource\OrderResource;
use Swag\PayPal\Test\Helper\ConstantsForTesting;
use Swag\PayPal\Test\Helper\PaymentTransactionTrait;
use Swag\PayPal\Test\Helper\SalesChannelContextTrait;
use Swag\PayPal\Test\Helper\ServicesTrait;
use Swag\PayPal\Test\Mock\PayPal\Client\_fixtures\V2\AuthorizeOrderAuthorization;
use Swag\PayPal\Test\Mock\PayPal\Client\_fixtures\V2\CaptureOrderCapture;
use Swag\PayPal\Test\Mock\PayPal\Client\_fixtures\V2\CreateOrderCapture;
use Swag\PayPal\Test\Mock\PayPal\Client\_fixtures\V2\GetCapturedOrderCapture;
use Swag\PayPal\Test\Mock\PayPal\Client\_fixtures\V2\GetOrderCapture;
use Swag\PayPal\Test\Mock\PayPal\Client\_fixtures\V2\GetRefundedOrderCapture;

class OrderResourceTest extends TestCase
{
    use BasicTestDataBehaviour;
    use DatabaseTransactionBehaviour;
    use PaymentTransactionTrait;
    use SalesChannelContextTrait;
    use ServicesTrait;

    public function testGetCreated(): void
    {
        $orderId = GetOrderCapture::ID;
        $order = $this->createResource()->get($orderId, Defaults::SALES_CHANNEL);

        static::assertSame($orderId, $order->getId());
        static::assertSame(PaymentIntentV2::CAPTURE, $order->getIntent());
        static::assertSame('APPROVED', $order->getStatus());
    }

    public function testGetCaptured(): void
    {
        $orderId = GetCapturedOrderCapture::ID;
        $order = $this->createResource()->get($orderId, Defaults::SALES_CHANNEL);

        static::assertSame($orderId, $order->getId());
        static::assertSame(PaymentIntentV2::CAPTURE, $order->getIntent());
        static::assertSame('COMPLETED', $order->getStatus());
    }

    public function testGetRefunded(): void
    {
        $orderId = GetRefundedOrderCapture::ID;
        $order = $this->createResource()->get($orderId, Defaults::SALES_CHANNEL);

        static::assertSame($orderId, $order->getId());
        static::assertSame(PaymentIntentV2::CAPTURE, $order->getIntent());
        static::assertSame('COMPLETED', $order->getStatus());
    }

    public function testCapture(): void
    {
        $order = $this->createResource()->capture('orderId', Defaults::SALES_CHANNEL, PartnerAttributionId::PAYPAL_CLASSIC);

        static::assertSame(CaptureOrderCapture::ID, $order->getId());
        $payments = $order->getPurchaseUnits()[0]->getPayments();
        static::assertNotNull($payments);
        $captures = $payments->getCaptures();
        static::assertNotNull($captures);
        static::assertTrue($captures[0]->isFinalCapture());
        static::assertNull($payments->getRefunds());
        static::assertNull($payments->getAuthorizations());
    }

    public function testCreate(): void
    {
        $orderBuilder = $this->createOrderBuilder();
        $paymentTransaction = $this->createPaymentTransactionStruct(ConstantsForTesting::VALID_ORDER_ID);
        $salesChannelContext = $this->createSalesChannelContext($this->getContainer(), new PaymentMethodCollection());
        $customer = $salesChannelContext->getCustomer();
        static::assertNotNull($customer);
        $order = $orderBuilder->getOrder(
            $paymentTransaction,
            $salesChannelContext,
            $customer
        );

        static::assertNotNull($order->getPurchaseUnits()[0]->getItems());

        $orderResponse = $this->createResource()->create($order, Defaults::SALES_CHANNEL, PartnerAttributionId::PAYPAL_CLASSIC);

        static::assertSame(CreateOrderCapture::ID, $orderResponse->getId());
        static::assertStringContainsString('token=' . CreateOrderCapture::ID, $orderResponse->getLinks()[1]->getHref());
    }

    public function testAuthorize(): void
    {
        $order = $this->createResource()->authorize('orderId', Defaults::SALES_CHANNEL, PartnerAttributionId::PAYPAL_CLASSIC);

        static::assertSame(AuthorizeOrderAuthorization::ID, $order->getId());
        $payments = $order->getPurchaseUnits()[0]->getPayments();
        static::assertNotNull($payments);
        $authorizations = $payments->getAuthorizations();
        static::assertNotNull($authorizations);
        static::assertSame('CREATED', $authorizations[0]->getStatus());
        static::assertNull($payments->getCaptures());
        static::assertNull($payments->getRefunds());
    }

    private function createResource(): OrderResource
    {
        return new OrderResource($this->createPayPalClientFactory());
    }
}
