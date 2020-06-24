<?php declare(strict_types=1);
/*
 * (c) shopware AG <info@shopware.com>
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Swag\PayPal\Test\Checkout\SPBCheckout;

use PHPUnit\Framework\TestCase;
use Shopware\Core\Checkout\Cart\Exception\CustomerNotLoggedInException;
use Shopware\Core\Checkout\Cart\Exception\OrderNotFoundException;
use Shopware\Core\Checkout\Cart\SalesChannel\CartService;
use Shopware\Core\Checkout\Payment\Exception\InvalidOrderException;
use Shopware\Core\Checkout\Payment\PaymentMethodCollection;
use Shopware\Core\Checkout\Test\Cart\Common\Generator;
use Shopware\Core\Framework\DataAbstractionLayer\EntityRepositoryInterface;
use Shopware\Core\Framework\Struct\ArrayStruct;
use Shopware\Core\Framework\Test\TestCaseBase\DatabaseTransactionBehaviour;
use Swag\PayPal\Checkout\SPBCheckout\SPBCheckoutController;
use Swag\PayPal\Payment\Builder\CartPaymentBuilder;
use Swag\PayPal\Payment\Builder\OrderPaymentBuilder;
use Swag\PayPal\Payment\Patch\PayerInfoPatchBuilder;
use Swag\PayPal\Payment\Patch\ShippingAddressPatchBuilder;
use Swag\PayPal\Setting\SwagPayPalSettingStruct;
use Swag\PayPal\Test\Helper\ConstantsForTesting;
use Swag\PayPal\Test\Helper\SalesChannelContextTrait;
use Swag\PayPal\Test\Helper\ServicesTrait;
use Swag\PayPal\Test\Mock\Repositories\OrderRepositoryMock;
use Swag\PayPal\Test\Mock\Setting\Service\SettingsServiceMock;
use Swag\PayPal\Util\LocaleCodeProvider;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

class SPBCheckoutControllerTest extends TestCase
{
    use DatabaseTransactionBehaviour;
    use SalesChannelContextTrait;
    use ServicesTrait;

    public function testCreatePayment(): void
    {
        $salesChannelContext = $this->createSalesChannelContext($this->getContainer(), new PaymentMethodCollection());

        $response = $this->createController()->createPayment($salesChannelContext, new Request());
        $content = $response->getContent();
        static::assertNotFalse($content);

        static::assertSame(Response::HTTP_OK, $response->getStatusCode());
        static::assertStringContainsString('{"token":"EC-', $content);
    }

    public function testCreatePaymentWithoutCustomer(): void
    {
        $salesChannelContext = Generator::createSalesChannelContext();
        $salesChannelContext->assign(['customer' => null]);

        $this->expectException(CustomerNotLoggedInException::class);
        $this->createController()->createPayment($salesChannelContext, new Request());
    }

    public function testCreatePaymentWithOrder(): void
    {
        $salesChannelContext = $this->createSalesChannelContext($this->getContainer(), new PaymentMethodCollection());
        $request = new Request([], ['orderId' => ConstantsForTesting::VALID_ORDER_ID]);

        $response = $this->createController()->createPayment($salesChannelContext, $request);
        $content = $response->getContent();
        static::assertNotFalse($content);

        static::assertSame(Response::HTTP_OK, $response->getStatusCode());
        static::assertStringContainsString('{"token":"EC-', $content);
    }

    public function testCreatePaymentWithoutOrder(): void
    {
        $salesChannelContext = $this->createSalesChannelContext($this->getContainer(), new PaymentMethodCollection());
        $salesChannelContext->getContext()->addExtension(OrderRepositoryMock::NO_ORDER, new ArrayStruct());
        $request = new Request([], ['orderId' => 'no-order-id']);

        $this->expectException(OrderNotFoundException::class);
        $this->expectExceptionMessage('Order with id "no-order-id" not found.');
        $this->createController()->createPayment($salesChannelContext, $request);
    }

    public function testCreatePaymentWithOrderWithoutTransactions(): void
    {
        $salesChannelContext = $this->createSalesChannelContext($this->getContainer(), new PaymentMethodCollection());
        $salesChannelContext->getContext()->addExtension(OrderRepositoryMock::NO_ORDER_TRANSACTIONS, new ArrayStruct());
        $request = new Request([], ['orderId' => 'no-order-transactions-id']);

        $this->expectException(InvalidOrderException::class);
        $this->expectExceptionMessage('The order with id no-order-transactions-id is invalid or could not be found.');
        $this->createController()->createPayment($salesChannelContext, $request);
    }

    public function testCreatePaymentWithOrderWithoutTransaction(): void
    {
        $salesChannelContext = $this->createSalesChannelContext($this->getContainer(), new PaymentMethodCollection());
        $salesChannelContext->getContext()->addExtension(OrderRepositoryMock::NO_ORDER_TRANSACTION, new ArrayStruct());
        $request = new Request([], ['orderId' => 'no-order-transaction-id']);

        $this->expectException(InvalidOrderException::class);
        $this->expectExceptionMessage('The order with id no-order-transaction-id is invalid or could not be found.');
        $this->createController()->createPayment($salesChannelContext, $request);
    }

    private function createController(): SPBCheckoutController
    {
        $container = $this->getContainer();
        /** @var CartService $cartService */
        $cartService = $container->get(CartService::class);

        $settings = new SwagPayPalSettingStruct();
        $settings->setClientId('testClientId');
        $settings->setClientSecret('testClientSecret');

        $settingsService = new SettingsServiceMock($settings);
        /** @var EntityRepositoryInterface $salesChannelRepo */
        $salesChannelRepo = $container->get('sales_channel.repository');
        /** @var LocaleCodeProvider $localeCodeProvider */
        $localeCodeProvider = $container->get(LocaleCodeProvider::class);
        /** @var EntityRepositoryInterface $currencyRepo */
        $currencyRepo = $container->get('currency.repository');

        $cartPaymentBuilder = new CartPaymentBuilder(
            $settingsService,
            $salesChannelRepo,
            $localeCodeProvider
        );

        $orderPaymentBuilder = new OrderPaymentBuilder(
            $settingsService,
            $salesChannelRepo,
            $localeCodeProvider,
            $currencyRepo
        );

        return new SPBCheckoutController(
            $cartPaymentBuilder,
            $orderPaymentBuilder,
            $cartService,
            $this->createPaymentResource(),
            new PayerInfoPatchBuilder(),
            new ShippingAddressPatchBuilder(),
            new OrderRepositoryMock()
        );
    }
}
