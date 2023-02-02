<?php declare(strict_types=1);
/*
 * (c) shopware AG <info@shopware.com>
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Swag\PayPal\Test\Storefront;

use PHPUnit\Framework\TestCase;
use Psr\Log\NullLogger;
use Shopware\Core\Checkout\Test\Cart\Common\Generator;
use Shopware\Core\Framework\Uuid\Uuid;
use Shopware\Storefront\Event\RouteRequest\HandlePaymentMethodRouteRequestEvent;
use Shopware\Storefront\Event\RouteRequest\PaymentMethodRouteRequestEvent;
use Swag\PayPal\Storefront\RequestSubscriber;
use Swag\PayPal\Test\RestApi\AssertArraySubsetTrait;
use Symfony\Component\HttpFoundation\Request;

class RequestSubscriberTest extends TestCase
{
    use AssertArraySubsetTrait;

    public function testGetSubscribedEvents(): void
    {
        $events = RequestSubscriber::getSubscribedEvents();

        static::assertCount(2, $events);
        static::assertSame('addHandlePaymentParameters', $events[HandlePaymentMethodRouteRequestEvent::class]);
        static::assertSame('addAfterOrderId', $events[PaymentMethodRouteRequestEvent::class]);
    }

    public function testAddNecessaryRequestParameter(): void
    {
        $subscriber = new RequestSubscriber(new NullLogger());

        $testData = $this->getParameterData();
        $storefrontRequest = new Request([], $testData, [
            '_route' => 'frontend.account.edit-order.update-order',
        ]);
        $storeApiRequest = new Request();
        $salesChannelContext = Generator::createSalesChannelContext();
        $event = new HandlePaymentMethodRouteRequestEvent($storefrontRequest, $storeApiRequest, $salesChannelContext);
        $subscriber->addHandlePaymentParameters($event);

        $requestParameters = $storeApiRequest->request;
        static::assertCount(\count(RequestSubscriber::PAYMENT_PARAMETERS), $requestParameters);
        static::assertArraySubset($testData, $requestParameters->all());
    }

    public function testAddNecessaryRequestParameterWrongRoute(): void
    {
        $subscriber = new RequestSubscriber(new NullLogger());

        $storefrontRequest = new Request([], $this->getParameterData(), ['_route' => 'wrong.route']);
        $storeApiRequest = new Request();
        $salesChannelContext = Generator::createSalesChannelContext();
        $event = new HandlePaymentMethodRouteRequestEvent($storefrontRequest, $storeApiRequest, $salesChannelContext);
        $subscriber->addHandlePaymentParameters($event);

        static::assertCount(0, $storeApiRequest->request);
    }

    public function testAfterOrderId(): void
    {
        $subscriber = new RequestSubscriber(new NullLogger());

        $storefrontRequest = new Request([], [], [
            'orderId' => 'tada',
            '_route' => 'frontend.account.edit-order.page',
        ]);
        $storeApiRequest = new Request();
        $salesChannelContext = Generator::createSalesChannelContext();
        $event = new PaymentMethodRouteRequestEvent($storefrontRequest, $storeApiRequest, $salesChannelContext);
        $subscriber->addAfterOrderId($event);

        static::assertCount(1, $storeApiRequest->attributes->all());
        static::assertSame('tada', $storeApiRequest->attributes->getAlnum('orderId'));
    }

    public function testAfterOrderIdWrongRoute(): void
    {
        $subscriber = new RequestSubscriber(new NullLogger());

        $storefrontRequest = new Request([], [], ['orderId' => 'tada', '_route' => 'wrong.route']);
        $storeApiRequest = new Request();
        $salesChannelContext = Generator::createSalesChannelContext();
        $event = new PaymentMethodRouteRequestEvent($storefrontRequest, $storeApiRequest, $salesChannelContext);
        $subscriber->addAfterOrderId($event);

        static::assertCount(0, $storeApiRequest->attributes);
    }

    /**
     * @return array<string, string>
     */
    private function getParameterData(): array
    {
        $testData = [];
        foreach (RequestSubscriber::PAYMENT_PARAMETERS as $paymentParameter) {
            $testData[$paymentParameter] = Uuid::randomHex();
        }

        return $testData;
    }
}
