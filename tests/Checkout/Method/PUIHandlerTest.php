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
use Shopware\Core\Checkout\Payment\Exception\SyncPaymentProcessException;
use Shopware\Core\Checkout\Test\Customer\Rule\OrderFixture;
use Shopware\Core\Framework\Context;
use Shopware\Core\Framework\DataAbstractionLayer\EntityRepositoryInterface;
use Shopware\Core\Framework\DataAbstractionLayer\Search\Criteria;
use Shopware\Core\Framework\DataAbstractionLayer\Search\Sorting\FieldSorting;
use Shopware\Core\Framework\Uuid\Uuid;
use Shopware\Core\Framework\Validation\DataBag\RequestDataBag;
use Shopware\Core\System\SalesChannel\SalesChannelContext;
use Swag\PayPal\Checkout\Payment\Method\PUIHandler;
use Swag\PayPal\Checkout\Payment\Service\TransactionDataService;
use Swag\PayPal\Checkout\PUI\Service\PUICustomerDataService;
use Swag\PayPal\OrdersApi\Builder\PUIOrderBuilder;
use Swag\PayPal\RestApi\PartnerAttributionId;
use Swag\PayPal\RestApi\V2\Resource\OrderResource;
use Swag\PayPal\Setting\Service\SettingsValidationService;
use Swag\PayPal\SwagPayPal;
use Swag\PayPal\Test\Helper\FullCheckoutTrait;
use Swag\PayPal\Test\Helper\OrderTransactionTrait;
use Swag\PayPal\Test\Helper\ServicesTrait;
use Swag\PayPal\Test\Helper\StateMachineStateTrait;
use Swag\PayPal\Test\Mock\PayPal\Client\_fixtures\V2\CreateOrderPUI;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\HttpFoundation\Session\Session;
use Symfony\Component\HttpFoundation\Session\Storage\MockArraySessionStorage;
use Symfony\Contracts\Translation\TranslatorInterface;

class PUIHandlerTest extends TestCase
{
    use FullCheckoutTrait;
    use ServicesTrait;
    use OrderFixture;
    use OrderTransactionTrait;
    use StateMachineStateTrait;

    public const PAYMENT_SOURCE_INFO_CANNOT_BE_VERIFIED = 'payment_source_info_cannot_be_verified@example.com';
    public const PAYMENT_SOURCE_DECLINED_BY_PROCESSOR = 'payment_source_declined_by_processor@example.com';

    private Session $session;

    public function testPay(): void
    {
        $productId = $this->createProduct();
        $context = $this->registerUser();
        $cart = $this->addToCart($productId, $context);
        $order = $this->placeOrder($cart, $context);
        $this->processPayment(
            $order,
            new RequestDataBag([
                PUIHandler::PUI_FRAUD_NET_SESSION_ID => Uuid::randomHex(),
                PUICustomerDataService::PUI_CUSTOMER_DATA_PHONE_NUMBER => '+491234956789',
                PUICustomerDataService::PUI_CUSTOMER_DATA_BIRTHDAY => ['year' => 1980, 'month' => 1, 'day' => 1],
            ]),
            $context,
            $this->getDefaultConfigData()
        );

        $this->assertOrderTransactionState(OrderTransactionStates::STATE_IN_PROGRESS, $this->getTransactionFromOrder($order)->getId(), $context->getContext());
        $this->assertCustomFields($this->getTransactionFromOrder($order)->getId(), CreateOrderPUI::ID, PartnerAttributionId::PAYPAL_PPCP);
    }

    public function testPayWithMissingCustomerData(): void
    {
        $productId = $this->createProduct();
        $context = $this->registerUser();
        $cart = $this->addToCart($productId, $context);
        $order = $this->placeOrder($cart, $context);

        $this->expectException(SyncPaymentProcessException::class);
        $this->expectExceptionMessageMatches('/The synchronous payment process was interrupted due to the following error:
Birthday is required for PUI for customer/');
        $this->processPayment(
            $order,
            new RequestDataBag([
                PUIHandler::PUI_FRAUD_NET_SESSION_ID => Uuid::randomHex(),
            ]),
            $context,
            $this->getDefaultConfigData()
        );
    }

    public function testPayWithMissingFraudnetId(): void
    {
        $productId = $this->createProduct();
        $context = $this->registerUser();
        $cart = $this->addToCart($productId, $context);
        $order = $this->placeOrder($cart, $context);

        $this->expectException(SyncPaymentProcessException::class);
        $this->expectExceptionMessage('The synchronous payment process was interrupted due to the following error:
Missing Fraudnet session id');
        $this->processPayment(
            $order,
            new RequestDataBag([
                PUICustomerDataService::PUI_CUSTOMER_DATA_PHONE_NUMBER => '+491234956789',
                PUICustomerDataService::PUI_CUSTOMER_DATA_BIRTHDAY => ['year' => 1980, 'month' => 1, 'day' => 1],
            ]),
            $context,
            $this->getDefaultConfigData()
        );
    }

    public function testPayWithInvalidSettingsException(): void
    {
        $productId = $this->createProduct();
        $context = $this->registerUser();
        $cart = $this->addToCart($productId, $context);
        $order = $this->placeOrder($cart, $context);

        $this->expectException(SyncPaymentProcessException::class);
        $this->expectExceptionMessage('The synchronous payment process was interrupted due to the following error:
Required setting "SwagPayPal.settings.clientId" is missing or invalid');
        $this->processPayment(
            $order,
            new RequestDataBag([
                PUIHandler::PUI_FRAUD_NET_SESSION_ID => Uuid::randomHex(),
                PUICustomerDataService::PUI_CUSTOMER_DATA_PHONE_NUMBER => '+491234956789',
                PUICustomerDataService::PUI_CUSTOMER_DATA_BIRTHDAY => ['year' => 1980, 'month' => 1, 'day' => 1],
            ]),
            $context,
            []
        );
    }

    public function testPayWithPaymentDeclinedByProcessor(): void
    {
        $productId = $this->createProduct();
        $context = $this->registerUser(self::PAYMENT_SOURCE_DECLINED_BY_PROCESSOR);
        $cart = $this->addToCart($productId, $context);
        $order = $this->placeOrder($cart, $context);

        try {
            $this->processPayment(
                $order,
                new RequestDataBag([
                    PUIHandler::PUI_FRAUD_NET_SESSION_ID => Uuid::randomHex(),
                    PUICustomerDataService::PUI_CUSTOMER_DATA_PHONE_NUMBER => '+491234956789',
                    PUICustomerDataService::PUI_CUSTOMER_DATA_BIRTHDAY => ['year' => 1980, 'month' => 1, 'day' => 1],
                ]),
                $context,
                $this->getDefaultConfigData()
            );
        } catch (SyncPaymentProcessException $e) {
            static::assertSame('The synchronous payment process was interrupted due to the following error:
The error "UNPROCESSABLE_ENTITY" occurred with the following message: The requested action could not be performed, semantically incorrect, or failed business validation. The provided payment source is declined by the processor. Please try again with a different payment source by creating a new order. PAYMENT_SOURCE_DECLINED_BY_PROCESSOR ', $e->getMessage());
            static::assertTrue($this->session->getFlashBag()->has('danger'));
        }
    }

    public function testPayWithPaymentInfoCannotBeVerified(): void
    {
        $productId = $this->createProduct();
        $context = $this->registerUser(self::PAYMENT_SOURCE_INFO_CANNOT_BE_VERIFIED);
        $cart = $this->addToCart($productId, $context);
        $order = $this->placeOrder($cart, $context);

        try {
            $this->processPayment(
                $order,
                new RequestDataBag([
                    PUIHandler::PUI_FRAUD_NET_SESSION_ID => Uuid::randomHex(),
                    PUICustomerDataService::PUI_CUSTOMER_DATA_PHONE_NUMBER => '+491234956789',
                    PUICustomerDataService::PUI_CUSTOMER_DATA_BIRTHDAY => ['year' => 1980, 'month' => 1, 'day' => 1],
                ]),
                $context,
                $this->getDefaultConfigData()
            );
        } catch (SyncPaymentProcessException $e) {
            static::assertSame('The synchronous payment process was interrupted due to the following error:
The error "UNPROCESSABLE_ENTITY" occurred with the following message: The requested action could not be performed, semantically incorrect, or failed business validation. The combination of the payment_source name, billing address, shipping name and shipping address could not be verified. Please correct this information and try again by creating a new order. PAYMENT_SOURCE_INFO_CANNOT_BE_VERIFIED ', $e->getMessage());
            static::assertTrue($this->session->getFlashBag()->has('danger'));
        }
    }

    private function processPayment(OrderEntity $order, RequestDataBag $requestData, SalesChannelContext $context, array $settings): void
    {
        $systemConfig = $this->createSystemConfigServiceMock($settings);
        $clientFactory = $this->createPayPalClientFactoryWithService($systemConfig);
        $orderResource = new OrderResource($clientFactory);
        $logger = new NullLogger();

        $criteria = new Criteria([$order->getId()]);
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

        /** @var EntityRepositoryInterface $orderRepository */
        $orderRepository = $this->getContainer()->get('order.repository');
        $order = $orderRepository->search($criteria, $context->getContext())->first();
        static::assertNotNull($order);

        $this->session = new Session(new MockArraySessionStorage());
        $request = new Request();
        $request->setSession($this->session);
        $requestStack = new RequestStack();
        $requestStack->push($request);

        /** @var TranslatorInterface $translator */
        $translator = $this->getContainer()->get('translator');

        $handler = new PUIHandler(
            new SettingsValidationService($systemConfig, new NullLogger()),
            $this->getContainer()->get(OrderTransactionStateHandler::class),
            $this->getContainer()->get(PUIOrderBuilder::class),
            $orderResource,
            $this->getContainer()->get(TransactionDataService::class),
            $this->getContainer()->get(PUICustomerDataService::class),
            $requestStack,
            $translator,
            $logger,
        );

        $transaction = $order->getTransactions()->last();
        $struct = new AsyncPaymentTransactionStruct($transaction, $order, 'http://return.url');
        $handler->pay($struct, $requestData, $context);
    }

    private function assertCustomFields(string $orderTransactionId, string $orderId, string $attributionId): void
    {
        /** @var EntityRepositoryInterface $orderTransactionRepo */
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
