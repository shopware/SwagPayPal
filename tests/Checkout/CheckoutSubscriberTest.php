<?php declare(strict_types=1);
/*
 * (c) shopware AG <info@shopware.com>
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Swag\PayPal\Test\Checkout;

use PHPUnit\Framework\TestCase;
use Psr\Log\NullLogger;
use Shopware\Core\Checkout\Order\OrderEntity;
use Shopware\Core\Checkout\Payment\PaymentMethodCollection;
use Shopware\Core\Framework\Context;
use Shopware\Core\Framework\DataAbstractionLayer\EntityRepository;
use Shopware\Core\Framework\DataAbstractionLayer\Search\Criteria;
use Shopware\Core\Framework\DataAbstractionLayer\Search\Filter\EqualsFilter;
use Shopware\Core\Framework\DataAbstractionLayer\Search\Filter\NotFilter;
use Shopware\Core\Framework\Plugin\Util\PluginIdProvider;
use Shopware\Storefront\Page\Account\Order\AccountEditOrderPage;
use Shopware\Storefront\Page\Account\Order\AccountEditOrderPageLoadedEvent;
use Swag\PayPal\Checkout\CheckoutSubscriber;
use Swag\PayPal\Checkout\Payment\PayPalPaymentHandler;
use Swag\PayPal\Pos\Payment\PosPayment;
use Swag\PayPal\SwagPayPal;
use Swag\PayPal\Test\Helper\CartTrait;
use Swag\PayPal\Test\Helper\Compatibility\Generator;
use Swag\PayPal\Test\Helper\ServicesTrait;
use Swag\PayPal\Util\Lifecycle\Method\PaymentMethodDataRegistry;
use Symfony\Component\HttpFoundation\Request;

/**
 * @internal
 */
class CheckoutSubscriberTest extends TestCase
{
    use CartTrait;
    use ServicesTrait;

    private CheckoutSubscriber $checkoutSubscriber;

    private PaymentMethodDataRegistry $methodDataRegistry;

    public function setUp(): void
    {
        $this->methodDataRegistry = $this->getContainer()->get(PaymentMethodDataRegistry::class);

        $this->checkoutSubscriber = new CheckoutSubscriber(
            $this->methodDataRegistry,
            new NullLogger()
        );
    }

    public function testEvents(): void
    {
        $events = CheckoutSubscriber::getSubscribedEvents();
        static::assertCount(1, $events);
    }

    public function testOrderEditPageDoesRemoveWithCartWithValueZero(): void
    {
        $event = $this->getOrderEditPageEvent();
        $event->getPage()->getOrder()->setPrice($this->createCartPrice(0.0, 0.0, 0.0));
        $this->checkoutSubscriber->onEditOrderPageLoaded($event);

        static::assertCount(0, $event->getPage()->getPaymentMethods());
    }

    private function getOrderEditPageEvent(): AccountEditOrderPageLoadedEvent
    {
        $context = Context::createDefaultContext();

        $paymentMethods = $this->getPaymentMethods($context);

        $salesChannelContext = Generator::createSalesChannelContext(
            $context,
            null,
            null,
            null,
            null,
            null,
            null,
            null,
            $paymentMethods->filterByProperty('handlerIdentifier', PayPalPaymentHandler::class)->first()
        );

        $page = new AccountEditOrderPage();
        $page->setPaymentMethods($paymentMethods);
        $order = new OrderEntity();
        $order->setPrice($this->createCartPrice(20.0, 20.0, 20.0));
        $page->setOrder($order);

        return new AccountEditOrderPageLoadedEvent($page, $salesChannelContext, new Request());
    }

    private function getPaymentMethods(Context $context): PaymentMethodCollection
    {
        /** @var EntityRepository $paymentMethodRepository */
        $paymentMethodRepository = $this->getContainer()->get('payment_method.repository');

        $pluginId = $this->getContainer()->get(PluginIdProvider::class)->getPluginIdByBaseClass(SwagPayPal::class, Context::createDefaultContext());

        $criteria = new Criteria();
        $criteria->addFilter(new EqualsFilter('pluginId', $pluginId));
        $criteria->addFilter(new NotFilter(NotFilter::CONNECTION_OR, [new EqualsFilter('handlerIdentifier', PosPayment::class)]));

        /** @var PaymentMethodCollection $paymentMethods */
        $paymentMethods = $paymentMethodRepository->search($criteria, $context)->getEntities();
        static::assertCount(\count($this->getContainer()->get(PaymentMethodDataRegistry::class)->getPaymentMethods()), $paymentMethods);

        return $paymentMethods;
    }
}
