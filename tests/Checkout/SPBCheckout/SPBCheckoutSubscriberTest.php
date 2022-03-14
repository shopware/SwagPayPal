<?php declare(strict_types=1);
/*
 * (c) shopware AG <info@shopware.com>
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Swag\PayPal\Test\Checkout\SPBCheckout;

use PHPUnit\Framework\TestCase;
use Psr\Log\NullLogger;
use Shopware\Core\Checkout\Payment\PaymentMethodCollection;
use Shopware\Storefront\Event\RouteRequest\HandlePaymentMethodRouteRequestEvent;
use Swag\PayPal\Checkout\Payment\Method\AbstractPaymentMethodHandler;
use Swag\PayPal\Checkout\Payment\PayPalPaymentHandler;
use Swag\PayPal\Checkout\SPBCheckout\SPBCheckoutSubscriber;
use Swag\PayPal\Test\Helper\CartTrait;
use Swag\PayPal\Test\Helper\PaymentMethodTrait;
use Swag\PayPal\Test\Helper\PaymentTransactionTrait;
use Swag\PayPal\Test\Helper\SalesChannelContextTrait;
use Swag\PayPal\Test\Helper\ServicesTrait;
use Symfony\Component\HttpFoundation\Request;

class SPBCheckoutSubscriberTest extends TestCase
{
    use CartTrait;
    use PaymentMethodTrait;
    use PaymentTransactionTrait;
    use SalesChannelContextTrait;
    use ServicesTrait;

    public function testGetSubscribedEvents(): void
    {
        $events = SPBCheckoutSubscriber::getSubscribedEvents();

        static::assertEmpty($events);
    }

    /**
     * @deprecated tag:v6.0.0 - will be removed
     */
    public function testAddNecessaryRequestParameter(): void
    {
        $subscriber = new SPBCheckoutSubscriber(new NullLogger());

        $testButtonId = 'testButtonId';
        $testOrderId = 'testOrderId';
        $storefrontRequest = new Request([], [
            PayPalPaymentHandler::PAYPAL_SMART_PAYMENT_BUTTONS_ID => $testButtonId,
            AbstractPaymentMethodHandler::PAYPAL_PAYMENT_ORDER_ID_INPUT_NAME => $testOrderId,
        ], [
            '_route' => 'frontend.account.edit-order.update-order',
        ]);
        $storeApiRequest = new Request();
        $salesChannelContext = $this->createSalesChannelContext($this->getContainer(), new PaymentMethodCollection());
        $event = new HandlePaymentMethodRouteRequestEvent($storefrontRequest, $storeApiRequest, $salesChannelContext);
        $subscriber->addNecessaryRequestParameter($event);

        $requestParameters = $storeApiRequest->request;
        static::assertCount(2, $requestParameters);
        static::assertTrue($requestParameters->has(PayPalPaymentHandler::PAYPAL_SMART_PAYMENT_BUTTONS_ID));
        static::assertTrue($requestParameters->has(AbstractPaymentMethodHandler::PAYPAL_PAYMENT_ORDER_ID_INPUT_NAME));
        static::assertSame($testButtonId, $requestParameters->get(PayPalPaymentHandler::PAYPAL_SMART_PAYMENT_BUTTONS_ID));
        static::assertSame($testOrderId, $requestParameters->get(AbstractPaymentMethodHandler::PAYPAL_PAYMENT_ORDER_ID_INPUT_NAME));
    }

    /**
     * @deprecated tag:v6.0.0 - will be removed
     */
    public function testAddNecessaryRequestParameterWrongRoute(): void
    {
        $subscriber = new SPBCheckoutSubscriber(new NullLogger());

        $storefrontRequest = new Request([], [], ['_route' => 'wrong.route']);
        $storeApiRequest = new Request();
        $salesChannelContext = $this->createSalesChannelContext($this->getContainer(), new PaymentMethodCollection());
        $event = new HandlePaymentMethodRouteRequestEvent($storefrontRequest, $storeApiRequest, $salesChannelContext);
        $subscriber->addNecessaryRequestParameter($event);

        $requestParameters = $storeApiRequest->request;
        static::assertCount(0, $requestParameters);
    }
}
