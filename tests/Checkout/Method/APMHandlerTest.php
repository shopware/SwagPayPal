<?php declare(strict_types=1);
/*
 * (c) shopware AG <info@shopware.com>
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Swag\PayPal\Test\Checkout\Method;

use PHPUnit\Framework\TestCase;
use Psr\Log\NullLogger;
use Shopware\Core\Checkout\Order\Aggregate\OrderTransaction\OrderTransactionEntity;
use Shopware\Core\Checkout\Order\Aggregate\OrderTransaction\OrderTransactionStateHandler;
use Shopware\Core\Checkout\Order\Aggregate\OrderTransaction\OrderTransactionStates;
use Shopware\Core\Checkout\Order\OrderEntity;
use Shopware\Core\Checkout\Payment\Cart\AsyncPaymentTransactionStruct;
use Shopware\Core\Checkout\Payment\Exception\AsyncPaymentProcessException;
use Shopware\Core\Framework\Context;
use Shopware\Core\Framework\DataAbstractionLayer\EntityRepository;
use Shopware\Core\Framework\DataAbstractionLayer\Search\Criteria;
use Shopware\Core\Framework\DataAbstractionLayer\Search\Sorting\FieldSorting;
use Shopware\Core\Framework\Log\Package;
use Shopware\Core\Framework\Test\TestCaseBase\IntegrationTestBehaviour;
use Shopware\Core\Framework\Validation\DataBag\RequestDataBag;
use Shopware\Core\System\SalesChannel\SalesChannelContext;
use Swag\PayPal\Checkout\Payment\Method\APMHandler;
use Swag\PayPal\Checkout\Payment\Service\TransactionDataService;
use Swag\PayPal\OrdersApi\Builder\APM\AbstractAPMOrderBuilder;
use Swag\PayPal\OrdersApi\Builder\APM\BancontactOrderBuilder;
use Swag\PayPal\OrdersApi\Builder\APM\BlikOrderBuilder;
use Swag\PayPal\OrdersApi\Builder\APM\EpsOrderBuilder;
use Swag\PayPal\OrdersApi\Builder\APM\GiropayOrderBuilder;
use Swag\PayPal\OrdersApi\Builder\APM\IdealOrderBuilder;
use Swag\PayPal\OrdersApi\Builder\APM\MultibancoOrderBuilder;
use Swag\PayPal\OrdersApi\Builder\APM\MyBankOrderBuilder;
use Swag\PayPal\OrdersApi\Builder\APM\OxxoOrderBuilder;
use Swag\PayPal\OrdersApi\Builder\APM\P24OrderBuilder;
use Swag\PayPal\OrdersApi\Builder\APM\SofortOrderBuilder;
use Swag\PayPal\OrdersApi\Builder\APM\TrustlyOrderBuilder;
use Swag\PayPal\RestApi\PartnerAttributionId;
use Swag\PayPal\RestApi\V2\Resource\OrderResource;
use Swag\PayPal\Setting\Service\SettingsValidationService;
use Swag\PayPal\SwagPayPal;
use Swag\PayPal\Test\Helper\FullCheckoutTrait;
use Swag\PayPal\Test\Helper\OrderTransactionTrait;
use Swag\PayPal\Test\Helper\ServicesTrait;
use Swag\PayPal\Test\Helper\StateMachineStateTrait;
use Swag\PayPal\Test\Mock\PayPal\Client\_fixtures\V2\CreateOrderAPM;
use Swag\PayPal\Test\Mock\PayPal\Client\PayPalClientFactoryMock;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Request;

/**
 * @internal
 */
#[Package('checkout')]
class APMHandlerTest extends TestCase
{
    use FullCheckoutTrait;
    use IntegrationTestBehaviour;
    use OrderTransactionTrait;
    use ServicesTrait;
    use StateMachineStateTrait;

    /**
     * @dataProvider dataProviderOrderBuilder
     */
    public function testPayAndFinalize(AbstractAPMOrderBuilder $orderBuilder): void
    {
        $productId = $this->createProduct();
        $context = $this->registerUser();
        $cart = $this->addToCart($productId, $context);
        $order = $this->placeOrder($cart, $context);
        $paymentHandler = $this->getPaymentHandler($orderBuilder, $this->getDefaultConfigData());
        $transactionId = $this->getTransactionFromOrder($order)->getId();
        $this->processPayment($order->getId(), new RequestDataBag(), $context, $paymentHandler);

        $this->assertOrderTransactionState(OrderTransactionStates::STATE_UNCONFIRMED, $transactionId, $context->getContext());
        $this->assertCustomFields($transactionId, CreateOrderAPM::ID, PartnerAttributionId::PAYPAL_PPCP);

        $this->finalizePayment($transactionId, new Request(), $context, $paymentHandler);
        $this->assertOrderTransactionState(OrderTransactionStates::STATE_UNCONFIRMED, $transactionId, $context->getContext());
    }

    /**
     * @dataProvider dataProviderOrderBuilder
     */
    public function testPayWithInvalidSettingsException(AbstractAPMOrderBuilder $orderBuilder): void
    {
        $productId = $this->createProduct();
        $context = $this->registerUser();
        $cart = $this->addToCart($productId, $context);
        $order = $this->placeOrder($cart, $context);

        $this->expectException(AsyncPaymentProcessException::class);
        $this->expectExceptionMessage('The asynchronous payment process was interrupted due to the following error:
Required setting "SwagPayPal.settings.clientId" is missing or invalid');
        $this->processPayment(
            $order->getId(),
            new RequestDataBag(),
            $context,
            $this->getPaymentHandler($orderBuilder, [])
        );
    }

    public function dataProviderOrderBuilder(): array
    {
        return [
            [$this->getContainer()->get(BancontactOrderBuilder::class)],
            [$this->getContainer()->get(BlikOrderBuilder::class)],
            // [$this->getContainer()->get(BoletoBancarioOrderBuilder::class)],
            [$this->getContainer()->get(EpsOrderBuilder::class)],
            [$this->getContainer()->get(GiropayOrderBuilder::class)],
            [$this->getContainer()->get(IdealOrderBuilder::class)],
            [$this->getContainer()->get(MultibancoOrderBuilder::class)],
            [$this->getContainer()->get(MyBankOrderBuilder::class)],
            [$this->getContainer()->get(OxxoOrderBuilder::class)],
            [$this->getContainer()->get(P24OrderBuilder::class)],
            [$this->getContainer()->get(SofortOrderBuilder::class)],
            [$this->getContainer()->get(TrustlyOrderBuilder::class)],
        ];
    }

    private function processPayment(string $orderId, RequestDataBag $requestData, SalesChannelContext $context, APMHandler $apmHandler): RedirectResponse
    {
        $criteria = new Criteria([$orderId]);
        $criteria->addAssociation('transactions.stateMachineState');
        $criteria->addAssociation('transactions.paymentMethod');
        $criteria->addAssociation('orderCustomer.customer');
        $criteria->addAssociation('orderCustomer.salutation');
        $criteria->addAssociation('transactions.paymentMethod.appPaymentMethod.app');
        $criteria->addAssociation('language');
        $criteria->addAssociation('currency');
        $criteria->addAssociation('deliveries.shippingOrderAddress.country');
        $criteria->addAssociation('billingAddress.country');
        $criteria->addAssociation('lineItems');
        $criteria->getAssociation('transactions')->addSorting(new FieldSorting('createdAt'));

        /** @var EntityRepository $orderRepository */
        $orderRepository = $this->getContainer()->get('order.repository');
        /** @var OrderEntity|null $order */
        $order = $orderRepository->search($criteria, $context->getContext())->first();
        static::assertNotNull($order);

        $struct = new AsyncPaymentTransactionStruct($this->getTransactionFromOrder($order), $order, 'http://return.url');

        return $apmHandler->pay($struct, $requestData, $context);
    }

    private function finalizePayment(string $orderTransactionId, Request $request, SalesChannelContext $context, APMHandler $apmHandler): void
    {
        $criteria = new Criteria([$orderTransactionId]);
        $criteria->addAssociation('order');
        $criteria->addAssociation('paymentMethod.appPaymentMethod.app');

        /** @var EntityRepository $orderTransactionRepository */
        $orderTransactionRepository = $this->getContainer()->get('order_transaction.repository');
        /** @var OrderTransactionEntity|null $orderTransaction */
        $orderTransaction = $orderTransactionRepository->search($criteria, $context->getContext())->first();
        static::assertNotNull($orderTransaction);
        $order = $orderTransaction->getOrder();
        static::assertNotNull($order);

        $struct = new AsyncPaymentTransactionStruct($orderTransaction, $order, '');
        $apmHandler->finalize($struct, $request, $context);
    }

    private function getPaymentHandler(AbstractAPMOrderBuilder $orderBuilder, array $settings): APMHandler
    {
        $systemConfig = $this->createSystemConfigServiceMock($settings);
        $clientFactory = new PayPalClientFactoryMock(new NullLogger());
        $orderResource = new OrderResource($clientFactory);
        $logger = new NullLogger();

        return new APMHandler(
            $this->getContainer()->get(TransactionDataService::class),
            $this->getContainer()->get(OrderTransactionStateHandler::class),
            new SettingsValidationService($systemConfig, $logger),
            $orderResource,
            $logger,
            $orderBuilder,
        );
    }

    private function assertCustomFields(string $orderTransactionId, string $orderId, string $attributionId): void
    {
        /** @var EntityRepository $orderTransactionRepo */
        $orderTransactionRepo = $this->getContainer()->get('order_transaction.repository');
        /** @var OrderTransactionEntity|null $orderTransaction */
        $orderTransaction = $orderTransactionRepo->search(new Criteria([$orderTransactionId]), Context::createDefaultContext())->first();
        static::assertNotNull($orderTransaction);

        static::assertSame($orderId, ($orderTransaction->getCustomFields() ?? [])[SwagPayPal::ORDER_TRANSACTION_CUSTOM_FIELDS_PAYPAL_ORDER_ID]);
        static::assertSame($attributionId, ($orderTransaction->getCustomFields() ?? [])[SwagPayPal::ORDER_TRANSACTION_CUSTOM_FIELDS_PAYPAL_PARTNER_ATTRIBUTION_ID]);
    }

    private function getTransactionFromOrder(OrderEntity $order): OrderTransactionEntity
    {
        $transactions = $order->getTransactions();
        static::assertNotNull($transactions);

        $transaction = $transactions->last();
        static::assertNotNull($transaction);

        return $transaction;
    }
}
