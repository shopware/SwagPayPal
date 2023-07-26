<?php declare(strict_types=1);
/*
 * (c) shopware AG <info@shopware.com>
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Swag\PayPal\Test\Checkout\SalesChannel;

use PHPUnit\Framework\TestCase;
use Psr\Log\NullLogger;
use Shopware\Core\Checkout\Cart\Exception\CustomerNotLoggedInException;
use Shopware\Core\Checkout\Cart\SalesChannel\CartService;
use Shopware\Core\Checkout\Payment\Exception\InvalidOrderException;
use Shopware\Core\Checkout\Payment\PaymentException;
use Shopware\Core\Checkout\Payment\PaymentMethodCollection;
use Shopware\Core\Checkout\Test\Cart\Common\Generator;
use Shopware\Core\Framework\Log\Package;
use Shopware\Core\Framework\ShopwareHttpException;
use Shopware\Core\Framework\Struct\ArrayStruct;
use Shopware\Core\Framework\Test\TestCaseBase\BasicTestDataBehaviour;
use Shopware\Core\Framework\Test\TestCaseBase\DatabaseTransactionBehaviour;
use Swag\PayPal\Checkout\Payment\Service\VaultTokenService;
use Swag\PayPal\Checkout\SalesChannel\CreateOrderRoute;
use Swag\PayPal\OrdersApi\Builder\OrderFromCartBuilder;
use Swag\PayPal\OrdersApi\Builder\Util\AddressProvider;
use Swag\PayPal\OrdersApi\Builder\Util\AmountProvider;
use Swag\PayPal\OrdersApi\Builder\Util\PurchaseUnitProvider;
use Swag\PayPal\RestApi\V2\Resource\OrderResource;
use Swag\PayPal\Setting\Settings;
use Swag\PayPal\Test\Helper\ConstantsForTesting;
use Swag\PayPal\Test\Helper\SalesChannelContextTrait;
use Swag\PayPal\Test\Helper\ServicesTrait;
use Swag\PayPal\Test\Mock\CustomIdProviderMock;
use Swag\PayPal\Test\Mock\EventDispatcherMock;
use Swag\PayPal\Test\Mock\LoggerMock;
use Swag\PayPal\Test\Mock\PayPal\Client\_fixtures\V2\CreateOrderCapture;
use Swag\PayPal\Test\Mock\Repositories\OrderRepositoryMock;
use Swag\PayPal\Util\LocaleCodeProvider;
use Swag\PayPal\Util\PriceFormatter;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * @internal
 */
#[Package('checkout')]
class CreateOrderRouteTest extends TestCase
{
    use BasicTestDataBehaviour;
    use DatabaseTransactionBehaviour;
    use SalesChannelContextTrait;
    use ServicesTrait;

    /**
     * @dataProvider dataProviderTestCreatePayment
     */
    public function testCreatePayment(bool $withCartLineItems): void
    {
        $salesChannelContext = $this->createSalesChannelContext(
            $this->getContainer(),
            new PaymentMethodCollection(),
            null,
            true,
            false,
            $withCartLineItems
        );

        $response = $this->createRoute()->createPayPalOrder($salesChannelContext, new Request());

        static::assertSame(Response::HTTP_OK, $response->getStatusCode());
        static::assertSame(CreateOrderCapture::ID, $response->getToken());
    }

    public function testCreatePaymentWithoutCustomer(): void
    {
        $salesChannelContext = Generator::createSalesChannelContext();
        $salesChannelContext->assign(['customer' => null]);

        $this->expectException(CustomerNotLoggedInException::class);
        $this->createRoute()->createPayPalOrder($salesChannelContext, new Request());
    }

    public function testCreatePaymentWithOrder(): void
    {
        $salesChannelContext = $this->createSalesChannelContext($this->getContainer(), new PaymentMethodCollection());
        $request = new Request([], ['orderId' => ConstantsForTesting::VALID_ORDER_ID]);

        $response = $this->createRoute()->createPayPalOrder($salesChannelContext, $request);

        static::assertSame(Response::HTTP_OK, $response->getStatusCode());
        static::assertSame(CreateOrderCapture::ID, $response->getToken());
    }

    public function testCreatePaymentWithoutOrder(): void
    {
        $salesChannelContext = $this->createSalesChannelContext($this->getContainer(), new PaymentMethodCollection());
        $salesChannelContext->getContext()->addExtension(OrderRepositoryMock::NO_ORDER, new ArrayStruct());
        $request = new Request([], ['orderId' => 'no-order-id']);

        $this->expectException(ShopwareHttpException::class);
        // @phpstan-ignore-next-line
        if (\class_exists(PaymentException::class) && \method_exists(PaymentException::class, 'unknownPaymentMethodByHandlerIdentifier')) {
            // Shopware >= 6.5.7.0
            $this->expectExceptionMessageMatches('/Could not find order with id \"no-order-id\"/');
        } else {
            $this->expectExceptionMessageMatches('/Order with id noorderid not found./');
        }
        $this->createRoute()->createPayPalOrder($salesChannelContext, $request);
    }

    public function testCreatePaymentWithOrderWithoutTransactions(): void
    {
        $salesChannelContext = $this->createSalesChannelContext($this->getContainer(), new PaymentMethodCollection());
        $salesChannelContext->getContext()->addExtension(OrderRepositoryMock::NO_ORDER_TRANSACTIONS, new ArrayStruct());
        $request = new Request([], ['orderId' => 'no-order-transactions-id']);

        $this->expectException(InvalidOrderException::class);
        $this->expectExceptionMessage('The order with id noordertransactionsid is invalid or could not be found.');
        $this->createRoute()->createPayPalOrder($salesChannelContext, $request);
    }

    public function testCreatePaymentWithOrderWithoutTransaction(): void
    {
        $salesChannelContext = $this->createSalesChannelContext($this->getContainer(), new PaymentMethodCollection());
        $salesChannelContext->getContext()->addExtension(OrderRepositoryMock::NO_ORDER_TRANSACTION, new ArrayStruct());
        $request = new Request([], ['orderId' => 'no-order-transaction-id']);

        $this->expectException(InvalidOrderException::class);
        $this->expectExceptionMessage('The order with id noordertransactionid is invalid or could not be found.');
        $this->createRoute()->createPayPalOrder($salesChannelContext, $request);
    }

    public function dataProviderTestCreatePayment(): array
    {
        return [[true], [false]];
    }

    private function createRoute(): CreateOrderRoute
    {
        $systemConfig = $this->createSystemConfigServiceMock([
            Settings::CLIENT_ID => 'testClientId',
            Settings::CLIENT_SECRET => 'testClientSecret',
        ]);

        $priceFormatter = new PriceFormatter();
        $amountProvider = new AmountProvider($priceFormatter);
        $addressProvider = new AddressProvider();
        $customIdProvider = new CustomIdProviderMock();

        $orderFromCartBuilder = new OrderFromCartBuilder(
            $priceFormatter,
            $systemConfig,
            new PurchaseUnitProvider($amountProvider, $addressProvider, $customIdProvider, $systemConfig),
            $addressProvider,
            $this->createMock(LocaleCodeProvider::class),
            new EventDispatcherMock(),
            new LoggerMock(),
            $this->createMock(VaultTokenService::class),
        );

        return new CreateOrderRoute(
            $this->getContainer()->get(CartService::class),
            new OrderRepositoryMock(),
            $this->createOrderBuilder($systemConfig),
            $orderFromCartBuilder,
            new OrderResource($this->createPayPalClientFactory()),
            new NullLogger()
        );
    }
}
