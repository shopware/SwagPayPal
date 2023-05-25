<?php declare(strict_types=1);
/*
 * (c) shopware AG <info@shopware.com>
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Swag\PayPal\Test\Checkout\Method;

use Psr\Log\NullLogger;
use Shopware\Core\Checkout\Order\Aggregate\OrderTransaction\OrderTransactionStateHandler;
use Shopware\Core\Checkout\Order\Aggregate\OrderTransaction\OrderTransactionStates;
use Shopware\Core\Checkout\Payment\Exception\SyncPaymentProcessException;
use Shopware\Core\Checkout\Payment\PaymentMethodCollection;
use Shopware\Core\Framework\Context;
use Swag\PayPal\Checkout\ACDC\ACDCValidator;
use Swag\PayPal\Checkout\Payment\Method\ACDCHandler;
use Swag\PayPal\Checkout\Payment\Service\OrderExecuteService;
use Swag\PayPal\Checkout\Payment\Service\OrderPatchService;
use Swag\PayPal\Checkout\Payment\Service\TransactionDataService;
use Swag\PayPal\OrdersApi\Builder\Util\AddressProvider;
use Swag\PayPal\OrdersApi\Builder\Util\AmountProvider;
use Swag\PayPal\OrdersApi\Builder\Util\ItemListProvider;
use Swag\PayPal\OrdersApi\Builder\Util\PurchaseUnitProvider;
use Swag\PayPal\OrdersApi\Patch\OrderNumberPatchBuilder;
use Swag\PayPal\OrdersApi\Patch\PurchaseUnitPatchBuilder;
use Swag\PayPal\RestApi\PartnerAttributionId;
use Swag\PayPal\RestApi\V2\Resource\OrderResource;
use Swag\PayPal\Setting\Service\SettingsValidationService;
use Swag\PayPal\Setting\Settings;
use Swag\PayPal\Test\Mock\CustomIdProviderMock;
use Swag\PayPal\Test\Mock\PayPal\Client\_fixtures\V2\CaptureOrderCapture;
use Swag\PayPal\Test\Mock\PayPal\Client\_fixtures\V2\GetOrderCaptureLiabilityShiftNo;
use Swag\PayPal\Test\Mock\PayPal\Client\_fixtures\V2\GetOrderCaptureLiabilityShiftUnknown;
use Swag\PayPal\Util\PriceFormatter;
use Symfony\Contracts\EventDispatcher\EventDispatcherInterface;

/**
 * @internal
 */
class ACDCHandlerTest extends AbstractSyncAPMHandlerTest
{
    public function testPayCaptureLiabilityShiftUnknown(): void
    {
        $handler = $this->createPaymentHandler($this->getDefaultConfigData());

        $transactionId = $this->getTransactionId(Context::createDefaultContext(), $this->getContainer());
        $salesChannelContext = $this->createSalesChannelContext($this->getContainer(), new PaymentMethodCollection());
        $paymentTransaction = $this->createPaymentTransactionStruct('some-order-id', $transactionId);

        $this->expectException(SyncPaymentProcessException::class);
        $this->expectExceptionMessage('The synchronous payment process was interrupted due to the following error:
Credit card validation failed, 3D secure was not validated.');
        $handler->pay($paymentTransaction, $this->createRequest(GetOrderCaptureLiabilityShiftUnknown::ID), $salesChannelContext);
    }

    public function testPayCaptureLiabilityShiftNo(): void
    {
        $handler = $this->createPaymentHandler($this->getDefaultConfigData());

        $transactionId = $this->getTransactionId(Context::createDefaultContext(), $this->getContainer());
        $salesChannelContext = $this->createSalesChannelContext($this->getContainer(), new PaymentMethodCollection());
        $paymentTransaction = $this->createPaymentTransactionStruct('some-order-id', $transactionId);

        $this->expectException(SyncPaymentProcessException::class);
        $this->expectExceptionMessage('The synchronous payment process was interrupted due to the following error:
Credit card validation failed, 3D secure was not validated.');
        $handler->pay($paymentTransaction, $this->createRequest(GetOrderCaptureLiabilityShiftNo::ID), $salesChannelContext);
    }

    public function testPayCaptureLiabilityShiftNoWithoutForced3DS(): void
    {
        $handler = $this->createPaymentHandler(\array_merge($this->getDefaultConfigData(), [Settings::ACDC_FORCE_3DS => false]));

        $transactionId = $this->getTransactionId(Context::createDefaultContext(), $this->getContainer());
        $salesChannelContext = $this->createSalesChannelContext($this->getContainer(), new PaymentMethodCollection());
        $paymentTransaction = $this->createPaymentTransactionStruct('some-order-id', $transactionId);

        $handler->pay($paymentTransaction, $this->createRequest(GetOrderCaptureLiabilityShiftNo::ID), $salesChannelContext);

        $this->assertOrderTransactionState(OrderTransactionStates::STATE_PAID, $transactionId, $salesChannelContext->getContext());
        $this->assertCustomFields(GetOrderCaptureLiabilityShiftNo::ID, PartnerAttributionId::PAYPAL_PPCP, CaptureOrderCapture::CAPTURE_ID);
        $this->assertPatchData($transactionId);
    }

    protected function createPaymentHandler(array $settings = []): ACDCHandler
    {
        $systemConfig = $this->createSystemConfigServiceMock($settings);
        $this->clientFactory = $this->createPayPalClientFactoryWithService($systemConfig);
        $orderResource = new OrderResource($this->clientFactory);
        $orderTransactionStateHandler = new OrderTransactionStateHandler($this->stateMachineRegistry);
        $logger = new NullLogger();

        return new ACDCHandler(
            new SettingsValidationService($systemConfig, new NullLogger()),
            $orderTransactionStateHandler,
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
            ),
            $logger,
            $orderResource,
            new ACDCValidator($systemConfig),
        );
    }

    protected function getPaymentHandlerClassName(): string
    {
        return ACDCHandler::class;
    }
}
