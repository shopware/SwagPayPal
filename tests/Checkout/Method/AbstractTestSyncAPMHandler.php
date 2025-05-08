<?php declare(strict_types=1);
/*
 * (c) shopware AG <info@shopware.com>
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Swag\PayPal\Test\Checkout\Method;

use PHPUnit\Framework\TestCase;
use Psr\Log\NullLogger;
use Shopware\Core\Checkout\Order\Aggregate\OrderTransaction\OrderTransactionDefinition;
use Shopware\Core\Checkout\Order\Aggregate\OrderTransaction\OrderTransactionStateHandler;
use Shopware\Core\Checkout\Order\Aggregate\OrderTransaction\OrderTransactionStates;
use Shopware\Core\Checkout\Payment\Cart\PaymentTransactionStruct;
use Shopware\Core\Checkout\Payment\PaymentException;
use Shopware\Core\Framework\Context;
use Shopware\Core\Framework\DataAbstractionLayer\EntityRepository;
use Shopware\Core\Framework\Log\Package;
use Shopware\Core\Framework\Test\TestCaseBase\IntegrationTestBehaviour;
use Shopware\Core\System\StateMachine\StateMachineRegistry;
use Shopware\PayPalSDK\Struct\V2\Patch as PatchV2;
use Shopware\PayPalSDK\Struct\V2\PatchCollection;
use Swag\PayPal\Checkout\CheckoutException;
use Swag\PayPal\Checkout\Exception\OrderFailedException;
use Swag\PayPal\Checkout\Payment\Method\AbstractPaymentMethodHandler;
use Swag\PayPal\Checkout\Payment\Service\OrderExecuteService;
use Swag\PayPal\Checkout\Payment\Service\OrderPatchService;
use Swag\PayPal\Checkout\Payment\Service\TransactionDataService;
use Swag\PayPal\Checkout\Payment\Service\VaultTokenService;
use Swag\PayPal\OrdersApi\Builder\AbstractOrderBuilder;
use Swag\PayPal\OrdersApi\Builder\Util\AddressProvider;
use Swag\PayPal\OrdersApi\Builder\Util\AmountProvider;
use Swag\PayPal\OrdersApi\Builder\Util\ItemListProvider;
use Swag\PayPal\OrdersApi\Builder\Util\PurchaseUnitProvider;
use Swag\PayPal\OrdersApi\Patch\OrderNumberPatchBuilder;
use Swag\PayPal\OrdersApi\Patch\PurchaseUnitPatchBuilder;
use Swag\PayPal\RestApi\PartnerAttributionId;
use Swag\PayPal\RestApi\V2\Resource\OrderResource;
use Swag\PayPal\Setting\Exception\PayPalSettingsInvalidException;
use Swag\PayPal\Setting\Service\CredentialsUtil;
use Swag\PayPal\Setting\Service\SettingsValidationService;
use Swag\PayPal\SwagPayPal;
use Swag\PayPal\Test\Checkout\Payment\PayPalPaymentHandlerTest;
use Swag\PayPal\Test\Helper\ConstantsForTesting;
use Swag\PayPal\Test\Helper\OrderTransactionTrait;
use Swag\PayPal\Test\Helper\PaymentTransactionTrait;
use Swag\PayPal\Test\Helper\SalesChannelContextTrait;
use Swag\PayPal\Test\Helper\ServicesTrait;
use Swag\PayPal\Test\Mock\CustomIdProviderMock;
use Swag\PayPal\Test\Mock\PayPal\Client\_fixtures\V2\AuthorizeOrderDenied;
use Swag\PayPal\Test\Mock\PayPal\Client\_fixtures\V2\CaptureOrderCapture;
use Swag\PayPal\Test\Mock\PayPal\Client\_fixtures\V2\CaptureOrderDeclined;
use Swag\PayPal\Test\Mock\PayPal\Client\_fixtures\V2\GetAuthorization;
use Swag\PayPal\Test\Mock\PayPal\Client\_fixtures\V2\GetOrderAuthorization;
use Swag\PayPal\Test\Mock\PayPal\Client\_fixtures\V2\GetOrderCapture;
use Swag\PayPal\Test\Mock\PayPalSDK\ApiContextFactoryMock;
use Swag\PayPal\Test\Mock\PayPalSDK\GatewayTestBehaviour;
use Swag\PayPal\Util\PriceFormatter;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Contracts\EventDispatcher\EventDispatcherInterface;

/**
 * @internal
 */
#[Package('checkout')]
abstract class AbstractTestSyncAPMHandler extends TestCase
{
    use GatewayTestBehaviour;
    use IntegrationTestBehaviour;
    use OrderTransactionTrait;
    use PaymentTransactionTrait;
    use SalesChannelContextTrait;
    use ServicesTrait;

    protected EntityRepository $orderTransactionRepo;

    protected StateMachineRegistry $stateMachineRegistry;

    protected function setUp(): void
    {
        $this->orderTransactionRepo = $this->getContainer()->get(OrderTransactionDefinition::ENTITY_NAME . '.repository');
        $this->stateMachineRegistry = $this->getContainer()->get(StateMachineRegistry::class);
    }

    public function testPayCapture(): void
    {
        $handler = $this->createPaymentHandler($this->getDefaultConfigData());

        $transactionId = $this->getTransactionId(Context::createDefaultContext(), $this->getContainer());
        $paymentTransaction = new PaymentTransactionStruct($transactionId);

        $handler->pay($this->createRequest(GetOrderCapture::ID), $paymentTransaction, Context::createDefaultContext(), null);

        $this->assertOrderTransactionState(OrderTransactionStates::STATE_PAID, $transactionId, Context::createDefaultContext());
        $this->assertCustomFields($transactionId, Context::createDefaultContext(), GetOrderCapture::ID, PartnerAttributionId::PAYPAL_PPCP, CaptureOrderCapture::CAPTURE_ID);
        $this->assertPatchData($transactionId);
    }

    public function testPayCaptureDeclined(): void
    {
        $handler = $this->createPaymentHandler($this->getDefaultConfigData());

        $transactionId = $this->getTransactionId(Context::createDefaultContext(), $this->getContainer());
        $paymentTransaction = new PaymentTransactionStruct($transactionId);

        $this->expectException(OrderFailedException::class);
        $this->expectExceptionMessage(\sprintf('Order "%s" failed', CaptureOrderDeclined::ID));
        $handler->pay($this->createRequest(CaptureOrderDeclined::ID), $paymentTransaction, Context::createDefaultContext(), null);
    }

    public function testPayAuthorize(): void
    {
        $handler = $this->createPaymentHandler($this->getDefaultConfigData());

        $transactionId = $this->getTransactionId(Context::createDefaultContext(), $this->getContainer());
        $paymentTransaction = new PaymentTransactionStruct($transactionId);

        $handler->pay($this->createRequest(GetOrderAuthorization::ID), $paymentTransaction, Context::createDefaultContext(), null);

        $this->assertOrderTransactionState(OrderTransactionStates::STATE_AUTHORIZED, $transactionId, Context::createDefaultContext());
        $this->assertCustomFields($transactionId, Context::createDefaultContext(), GetOrderAuthorization::ID, PartnerAttributionId::PAYPAL_PPCP, GetAuthorization::ID);
        $this->assertPatchData($transactionId);
    }

    public function testPayAuthorizeDenied(): void
    {
        $handler = $this->createPaymentHandler($this->getDefaultConfigData());

        $transactionId = $this->getTransactionId(Context::createDefaultContext(), $this->getContainer());
        $paymentTransaction = new PaymentTransactionStruct($transactionId);

        $this->expectException(OrderFailedException::class);
        $this->expectExceptionMessage(\sprintf('Order "%s" failed', AuthorizeOrderDenied::ID));
        $handler->pay($this->createRequest(AuthorizeOrderDenied::ID), $paymentTransaction, Context::createDefaultContext(), null);
    }

    public function testPayWithExceptionDuringPayPalCommunication(): void
    {
        $handler = $this->createPaymentHandler($this->getDefaultConfigData());

        $transactionId = $this->getTransactionId(Context::createDefaultContext(), $this->getContainer());
        $paymentTransaction = new PaymentTransactionStruct($transactionId);

        $this->expectException(PaymentException::class);
        $this->expectExceptionMessage('The error "UNPROCESSABLE_ENTITY" occurred with the following message: The requested action could not be completed, was semantically incorrect, or failed business validation. | [INSTRUMENT_DECLINED] The instrument presented was either declined by the processor or bank, or it can\'t be used for this payment.');
        $handler->pay($this->createRequest(PayPalPaymentHandlerTest::PAYPAL_ORDER_ID_INSTRUMENT_DECLINED), $paymentTransaction, Context::createDefaultContext(), null);
    }

    public function testPayWithInvalidSettingsException(): void
    {
        $handler = $this->createPaymentHandler();
        $transactionId = $this->getTransactionId(Context::createDefaultContext(), $this->getContainer());
        $paymentTransaction = new PaymentTransactionStruct($transactionId);

        $this->expectException(PayPalSettingsInvalidException::class);
        $handler->pay($this->createRequest(GetOrderCapture::ID), $paymentTransaction, Context::createDefaultContext(), null);
    }

    public function testPayWithoutValidOrderId(): void
    {
        $handler = $this->createPaymentHandler($this->getDefaultConfigData());
        $transactionId = $this->getTransactionId(Context::createDefaultContext(), $this->getContainer());

        $paymentTransaction = new PaymentTransactionStruct($transactionId);
        $this->expectException(CheckoutException::class);
        $this->expectExceptionMessage('PayPal Order ID does not exist in the request. The payment method ' . $handler::class . ' requires a prepared PayPal order.');
        $handler->pay($this->createRequest(), $paymentTransaction, Context::createDefaultContext(), null);
    }

    public function testPayWithDuplicateTransaction(): void
    {
        $handler = $this->createPaymentHandler($this->getDefaultConfigData());

        $transactionId = $this->getTransactionId(Context::createDefaultContext(), $this->getContainer());
        $paymentTransaction = new PaymentTransactionStruct($transactionId);
        CaptureOrderCapture::setDuplicateOrderNumber(true);

        $handler->pay($this->createRequest(PayPalPaymentHandlerTest::PAYPAL_ORDER_ID_DUPLICATE_ORDER_NUMBER), $paymentTransaction, Context::createDefaultContext(), null);

        $this->assertOrderTransactionState(OrderTransactionStates::STATE_PAID, $transactionId, Context::createDefaultContext());
        $this->assertCustomFields($transactionId, Context::createDefaultContext(), PayPalPaymentHandlerTest::PAYPAL_ORDER_ID_DUPLICATE_ORDER_NUMBER, PartnerAttributionId::PAYPAL_PPCP, CaptureOrderCapture::CAPTURE_ID);
        $this->assertPatchData($transactionId, true);
    }

    public function assertPatchData(string $orderTransactionId, bool $isDuplicateTransaction = false): void
    {
        $body = self::getClient()->getLastWhere(static fn ($context) => $context->getRequest()->getMethod() === 'PATCH')?->getRequestBody();
        static::assertIsArray($body);
        $patches = PatchCollection::createFromAssociative($body);
        static::assertCount(1, $patches);
        $patch = $patches->getAt(0);
        static::assertNotNull($patch);

        static::assertInstanceOf(PatchV2::class, $patch);
        if ($isDuplicateTransaction && $patch->getPath() === '/purchase_units/@reference_id==\'default\'/invoice_id') {
            static::assertSame(PatchV2::OPERATION_REMOVE, $patch->getOp());
        } else {
            $value = $patch->getValue();
            static::assertIsArray($value);
            static::assertSame(ConstantsForTesting::TEST_ORDER_NUMBER, $value['invoice_id']);
            static::assertStringContainsString($orderTransactionId, $value['custom_id']);
            static::assertSame(PatchV2::OPERATION_REPLACE, $patch->getOp());
        }
    }

    /**
     * @return class-string<AbstractPaymentMethodHandler>
     */
    abstract protected function getPaymentHandlerClassName(): string;

    protected function createPaymentHandler(array $settings = []): AbstractPaymentMethodHandler
    {
        $systemConfig = $this->createSystemConfigServiceMock($settings);
        $orderResource = new OrderResource(self::orderGateway(), new ApiContextFactoryMock());
        $orderTransactionStateHandler = new OrderTransactionStateHandler($this->stateMachineRegistry);
        $logger = new NullLogger();

        $handlerClass = $this->getPaymentHandlerClassName();

        return new $handlerClass(
            new SettingsValidationService($systemConfig, new NullLogger()),
            $this->stateMachineRegistry,
            new OrderExecuteService(
                $orderResource,
                $orderTransactionStateHandler,
                new OrderNumberPatchBuilder(),
                $logger
            ),
            new OrderPatchService(
                $systemConfig,
                new PurchaseUnitPatchBuilder(
                    new PurchaseUnitProvider(
                        new AmountProvider(new PriceFormatter()),
                        new AddressProvider(),
                        new CustomIdProviderMock(),
                        $systemConfig
                    ),
                    new ItemListProvider(
                        new PriceFormatter(),
                        $this->createMock(EventDispatcherInterface::class),
                        new NullLogger(),
                    ),
                ),
                $orderResource,
            ),
            new TransactionDataService(
                $this->orderTransactionRepo,
                new CredentialsUtil($systemConfig),
            ),
            $orderResource,
            new VaultTokenService(
                $this->createMock(EntityRepository::class),
                $this->createMock(EntityRepository::class),
                null,
            ),
            $this->orderTransactionRepo,
            $this->createMock(AbstractOrderBuilder::class),
        );
    }

    protected function createRequest(?string $orderId = null): Request
    {
        return new Request([], [
            AbstractPaymentMethodHandler::PAYPAL_PAYMENT_ORDER_ID_INPUT_NAME => $orderId,
        ]);
    }

    protected function assertCustomFields(string $orderTransactionId, Context $context, string $orderId, string $attributionId, ?string $resourceId): void
    {
        $transaction = $this->getTransaction($orderTransactionId, $this->getContainer(), $context);

        static::assertSame($orderId, $transaction?->getCustomFieldsValue(SwagPayPal::ORDER_TRANSACTION_CUSTOM_FIELDS_PAYPAL_ORDER_ID));
        static::assertSame($attributionId, $transaction->getCustomFieldsValue(SwagPayPal::ORDER_TRANSACTION_CUSTOM_FIELDS_PAYPAL_PARTNER_ATTRIBUTION_ID));
        static::assertSame($resourceId, $transaction->getCustomFieldsValue(SwagPayPal::ORDER_TRANSACTION_CUSTOM_FIELDS_PAYPAL_RESOURCE_ID));
    }
}
