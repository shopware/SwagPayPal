<?php declare(strict_types=1);
/*
 * (c) shopware AG <info@shopware.com>
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Swag\PayPal\Test\Checkout\PUI;

use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Psr\Log\LoggerInterface;
use Psr\Log\NullLogger;
use Shopware\Core\Checkout\Cart\Cart;
use Shopware\Core\Checkout\Cart\Error\ErrorCollection;
use Shopware\Core\Checkout\Order\Aggregate\OrderTransaction\OrderTransactionCollection;
use Shopware\Core\Checkout\Order\Aggregate\OrderTransaction\OrderTransactionEntity;
use Shopware\Core\Checkout\Order\OrderEntity;
use Shopware\Core\Checkout\Payment\Cart\Error\PaymentMethodBlockedError;
use Shopware\Core\Framework\Log\Package;
use Shopware\Core\System\SalesChannel\SalesChannelContext;
use Shopware\Core\Test\Generator;
use Shopware\Storefront\Page\Account\Order\AccountEditOrderPage;
use Shopware\Storefront\Page\Account\Order\AccountEditOrderPageLoadedEvent;
use Shopware\Storefront\Page\Checkout\Confirm\CheckoutConfirmPage;
use Shopware\Storefront\Page\Checkout\Confirm\CheckoutConfirmPageLoadedEvent;
use Shopware\Storefront\Page\Checkout\Finish\CheckoutFinishPage;
use Shopware\Storefront\Page\Checkout\Finish\CheckoutFinishPageLoadedEvent;
use Swag\PayPal\Checkout\Payment\Method\PUIHandler;
use Swag\PayPal\Checkout\PUI\PUIFraudNetData;
use Swag\PayPal\Checkout\PUI\PUIPaymentInstructionData;
use Swag\PayPal\Checkout\PUI\PUISubscriber;
use Swag\PayPal\Checkout\PUI\Service\PUIFraudNetDataService;
use Swag\PayPal\Checkout\PUI\Service\PUIPaymentInstructionDataService;
use Swag\PayPal\Setting\Exception\PayPalSettingsInvalidException;
use Swag\PayPal\Setting\Service\SettingsValidationServiceInterface;
use Symfony\Component\HttpFoundation\Request;

/**
 * @internal
 */
#[Package('checkout')]
class PUISubscriberTest extends TestCase
{
    private PUISubscriber $subscriber;

    /**
     * @var SettingsValidationServiceInterface&MockObject
     */
    private SettingsValidationServiceInterface $settingsValidationService;

    /**
     * @var PUIFraudNetDataService&MockObject
     */
    private PUIFraudNetDataService $puiFraudNetDataService;

    /**
     * @var PUIPaymentInstructionDataService&MockObject
     */
    private PUIPaymentInstructionDataService $puiPaymentInstructionDataService;

    private LoggerInterface $logger;

    private SalesChannelContext $salesChannelContext;

    protected function setUp(): void
    {
        $this->settingsValidationService = $this->createMock(SettingsValidationServiceInterface::class);
        $this->puiFraudNetDataService = $this->createMock(PUIFraudNetDataService::class);
        $this->puiPaymentInstructionDataService = $this->createMock(PUIPaymentInstructionDataService::class);
        $this->logger = new NullLogger();

        $this->salesChannelContext = Generator::generateSalesChannelContext();

        $this->subscriber = new PUISubscriber(
            $this->settingsValidationService,
            $this->puiFraudNetDataService,
            $this->puiPaymentInstructionDataService,
            $this->logger
        );
    }

    public function testGetSubscribedEvents(): void
    {
        $expectedEvents = [
            AccountEditOrderPageLoadedEvent::class => 'onAccountOrderEditLoaded',
            CheckoutConfirmPageLoadedEvent::class => 'onCheckoutConfirmLoaded',
            CheckoutFinishPageLoadedEvent::class => 'onCheckoutFinishLoaded',
        ];

        $actualEvents = PUISubscriber::getSubscribedEvents();

        static::assertEquals($expectedEvents, $actualEvents);
    }

    public function testOnAccountOrderEditLoadedWithValidSettings(): void
    {
        $paymentMethod = $this->salesChannelContext->getPaymentMethod();
        $paymentMethod->setHandlerIdentifier(PUIHandler::class);
        $this->settingsValidationService->expects($this->once())->method('validate');
        $fraudNetData = new PUIFraudNetData();
        $this->puiFraudNetDataService->method('buildCheckoutData')->willReturn($fraudNetData);

        $page = new AccountEditOrderPage();
        $event = new AccountEditOrderPageLoadedEvent($page, $this->salesChannelContext, new Request());

        $this->subscriber->onAccountOrderEditLoaded($event);
        $extension = $page->getExtension(PUISubscriber::PAYPAL_PUI_FRAUDNET_PAGE_EXTENSION_ID);
        static::assertSame($fraudNetData, $extension);
    }

    public function testOnAccountOrderEditLoadedWithInvalidPaymentMethod(): void
    {
        $paymentMethod = $this->salesChannelContext->getPaymentMethod();
        $paymentMethod->setHandlerIdentifier('InvalidHandler');
        $this->puiFraudNetDataService->expects($this->never())->method('buildCheckoutData');

        $page = new AccountEditOrderPage();
        $event = new AccountEditOrderPageLoadedEvent($page, $this->salesChannelContext, new Request());

        $this->subscriber->onAccountOrderEditLoaded($event);
        static::assertNull($page->getExtension(PUISubscriber::PAYPAL_PUI_FRAUDNET_PAGE_EXTENSION_ID));
    }

    public function testOnAccountOrderEditLoadedWithInvalidSettings(): void
    {
        $paymentMethod = $this->salesChannelContext->getPaymentMethod();
        $paymentMethod->setHandlerIdentifier(PUIHandler::class);
        $this->settingsValidationService->method('validate')->willThrowException(new PayPalSettingsInvalidException('Invalid settings'));
        $this->puiFraudNetDataService->expects($this->never())->method('buildCheckoutData');

        $page = new AccountEditOrderPage();
        $event = new AccountEditOrderPageLoadedEvent($page, $this->salesChannelContext, new Request());

        $this->subscriber->onAccountOrderEditLoaded($event);
        static::assertNull($page->getExtension(PUISubscriber::PAYPAL_PUI_FRAUDNET_PAGE_EXTENSION_ID));
    }

    public function testOnCheckoutConfirmLoadedWithValidSettings(): void
    {
        $paymentMethod = $this->salesChannelContext->getPaymentMethod();
        $paymentMethod->setHandlerIdentifier(PUIHandler::class);
        $this->settingsValidationService->expects($this->once())->method('validate');
        $fraudNetData = new PUIFraudNetData();
        $this->puiFraudNetDataService->method('buildCheckoutData')->willReturn($fraudNetData);

        $cart = new Cart('test');
        $cart->setErrors(new ErrorCollection());
        $page = new CheckoutConfirmPage();
        $page->setCart($cart);
        $event = new CheckoutConfirmPageLoadedEvent($page, $this->salesChannelContext, new Request());

        $this->subscriber->onCheckoutConfirmLoaded($event);
        $extension = $page->getExtension(PUISubscriber::PAYPAL_PUI_FRAUDNET_PAGE_EXTENSION_ID);
        static::assertSame($fraudNetData, $extension);
    }

    public function testOnCheckoutConfirmLoadedWithInvalidPaymentMethod(): void
    {
        $paymentMethod = $this->salesChannelContext->getPaymentMethod();
        $paymentMethod->setHandlerIdentifier('InvalidHandler');
        $this->puiFraudNetDataService->expects($this->never())->method('buildCheckoutData');

        $cart = new Cart('test');
        $cart->setErrors(new ErrorCollection());
        $page = new CheckoutConfirmPage();
        $page->setCart($cart);
        $event = new CheckoutConfirmPageLoadedEvent($page, $this->salesChannelContext, new Request());

        $this->subscriber->onCheckoutConfirmLoaded($event);
        static::assertNull($page->getExtension(PUISubscriber::PAYPAL_PUI_FRAUDNET_PAGE_EXTENSION_ID));
    }

    public function testOnCheckoutConfirmLoadedWithInvalidSettings(): void
    {
        $paymentMethod = $this->salesChannelContext->getPaymentMethod();
        $paymentMethod->setHandlerIdentifier(PUIHandler::class);
        $this->settingsValidationService->method('validate')->willThrowException(new PayPalSettingsInvalidException('Invalid settings'));
        $this->puiFraudNetDataService->expects($this->never())->method('buildCheckoutData');

        $cart = new Cart('test');
        $cart->setErrors(new ErrorCollection());
        $page = new CheckoutConfirmPage();
        $page->setCart($cart);
        $event = new CheckoutConfirmPageLoadedEvent($page, $this->salesChannelContext, new Request());

        $this->subscriber->onCheckoutConfirmLoaded($event);
        static::assertNull($page->getExtension(PUISubscriber::PAYPAL_PUI_FRAUDNET_PAGE_EXTENSION_ID));
    }

    public function testOnCheckoutConfirmLoadedWithBlockingErrors(): void
    {
        $paymentMethod = $this->salesChannelContext->getPaymentMethod();
        $paymentMethod->setHandlerIdentifier(PUIHandler::class);
        $this->settingsValidationService->expects($this->once())->method('validate');
        $this->puiFraudNetDataService->expects($this->never())->method('buildCheckoutData');

        $cart = new Cart('test');
        $errors = new ErrorCollection();
        // @deprecated tag:v6.8.0 - The parameter order will change in v6.8.0
        $errors->add(new PaymentMethodBlockedError('test-payment-method'));
        $cart->setErrors($errors);
        $page = new CheckoutConfirmPage();
        $page->setCart($cart);
        $event = new CheckoutConfirmPageLoadedEvent($page, $this->salesChannelContext, new Request());

        $this->subscriber->onCheckoutConfirmLoaded($event);
        static::assertNull($page->getExtension(PUISubscriber::PAYPAL_PUI_FRAUDNET_PAGE_EXTENSION_ID));
    }

    public function testOnCheckoutFinishLoadedWithValidSettings(): void
    {
        $this->settingsValidationService->expects($this->once())->method('validate');
        $paymentInstructionData = new PUIPaymentInstructionData();
        $this->puiPaymentInstructionDataService->method('buildFinishData')->willReturn($paymentInstructionData);

        $transaction = new OrderTransactionEntity();
        $transaction->setId('test-id');
        $transactions = new OrderTransactionCollection([$transaction]);
        $order = new OrderEntity();
        $order->setTransactions($transactions);
        $page = new CheckoutFinishPage();
        $page->setOrder($order);

        // @deprecated tag:v11.0.0 - remove if condition with min-version of 6.7.2.0, keep content
        // @phpstan-ignore-next-line method may or may not exist depending on Shopware version
        if (method_exists($page, 'setLogoutCustomer')) {
            $page->setLogoutCustomer(true);
        }
        $event = new CheckoutFinishPageLoadedEvent($page, $this->salesChannelContext, new Request());

        $this->subscriber->onCheckoutFinishLoaded($event);
        $extension = $page->getExtension(PUISubscriber::PAYPAL_PUI_PAYMENT_INSTRUCTIONS_PAGE_EXTENSION_ID);
        static::assertSame($paymentInstructionData, $extension);

        // @deprecated tag:v11.0.0 - remove if condition with min-version of 6.7.2.0, keep content
        // @phpstan-ignore-next-line method may or may not exist depending on Shopware version
        if (method_exists($page, 'isLogoutCustomer')) {
            static::assertFalse($page->isLogoutCustomer());
        }
    }

    public function testOnCheckoutFinishLoadedWithInvalidSettings(): void
    {
        $this->settingsValidationService->method('validate')->willThrowException(new PayPalSettingsInvalidException('Invalid settings'));
        $this->puiPaymentInstructionDataService->expects($this->never())->method('buildFinishData');

        $transaction = new OrderTransactionEntity();
        $transaction->setId('test-id');
        $transactions = new OrderTransactionCollection([$transaction]);
        $order = new OrderEntity();
        $order->setTransactions($transactions);
        $page = new CheckoutFinishPage();
        $page->setOrder($order);
        $event = new CheckoutFinishPageLoadedEvent($page, $this->salesChannelContext, new Request());

        $this->subscriber->onCheckoutFinishLoaded($event);
        static::assertNull($page->getExtension(PUISubscriber::PAYPAL_PUI_PAYMENT_INSTRUCTIONS_PAGE_EXTENSION_ID));
    }

    public function testOnCheckoutFinishLoadedWithNoTransactions(): void
    {
        $this->settingsValidationService->expects($this->once())->method('validate');
        $this->puiPaymentInstructionDataService->expects($this->never())->method('buildFinishData');

        $order = new OrderEntity();
        $page = new CheckoutFinishPage();
        $page->setOrder($order);
        $event = new CheckoutFinishPageLoadedEvent($page, $this->salesChannelContext, new Request());

        $this->subscriber->onCheckoutFinishLoaded($event);
        static::assertNull($page->getExtension(PUISubscriber::PAYPAL_PUI_PAYMENT_INSTRUCTIONS_PAGE_EXTENSION_ID));
    }

    public function testOnCheckoutFinishLoadedWithEmptyTransactions(): void
    {
        $this->settingsValidationService->expects($this->once())->method('validate');
        $this->puiPaymentInstructionDataService->expects($this->never())->method('buildFinishData');

        $transactions = new OrderTransactionCollection([]);
        $order = new OrderEntity();
        $order->setTransactions($transactions);
        $page = new CheckoutFinishPage();
        $page->setOrder($order);
        $event = new CheckoutFinishPageLoadedEvent($page, $this->salesChannelContext, new Request());

        $this->subscriber->onCheckoutFinishLoaded($event);
        static::assertNull($page->getExtension(PUISubscriber::PAYPAL_PUI_PAYMENT_INSTRUCTIONS_PAGE_EXTENSION_ID));
    }

    public function testOnCheckoutFinishLoadedWithNoPaymentInstructionData(): void
    {
        $this->settingsValidationService->expects($this->once())->method('validate');
        $this->puiPaymentInstructionDataService->method('buildFinishData')->willReturn(null);

        $transaction = new OrderTransactionEntity();
        $transaction->setId('test-id');
        $transactions = new OrderTransactionCollection([$transaction]);
        $order = new OrderEntity();
        $order->setTransactions($transactions);
        $page = new CheckoutFinishPage();
        $page->setOrder($order);
        $event = new CheckoutFinishPageLoadedEvent($page, $this->salesChannelContext, new Request());

        $this->subscriber->onCheckoutFinishLoaded($event);
        static::assertNull($page->getExtension(PUISubscriber::PAYPAL_PUI_PAYMENT_INSTRUCTIONS_PAGE_EXTENSION_ID));
    }
}
