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
use Shopware\Core\Checkout\Payment\Cart\PaymentTransactionStruct;
use Shopware\Core\Framework\Context;
use Shopware\Core\Framework\Log\Package;
use Shopware\PayPalSDK\Test\Request\TestRequestContext;
use Swag\PayPal\Checkout\Payment\Method\AbstractPaymentMethodHandler;
use Swag\PayPal\Checkout\Payment\Method\PayLaterHandler;
use Swag\PayPal\Checkout\Payment\Service\OrderExecuteService;
use Swag\PayPal\Checkout\Payment\Service\OrderPatchService;
use Swag\PayPal\Checkout\Payment\Service\OrderTransactionService;
use Swag\PayPal\Checkout\Payment\Service\TransactionDataService;
use Swag\PayPal\Checkout\Payment\Service\VaultTokenService;
use Swag\PayPal\Checkout\SalesChannel\CreateOrderRoute;
use Swag\PayPal\OrdersApi\Patch\OrderNumberPatchBuilder;
use Swag\PayPal\OrdersApi\Patch\PurchaseUnitPatchBuilder;
use Swag\PayPal\RestApi\V2\Resource\OrderResource;
use Swag\PayPal\Setting\Service\CredentialsUtil;
use Swag\PayPal\Setting\Service\SettingsValidationService;
use Swag\PayPal\Setting\Settings;
use Swag\PayPal\SwagPayPal;
use Swag\PayPal\Test\Mock\PayPalSDK\ApiContextFactoryMock;
use Swag\PayPal\Test\Mock\PayPalSDK\MockRequestHandler;
use Symfony\Component\HttpFoundation\Request;

/**
 * @internal
 */
#[Package('checkout')]
class PayLaterHandlerTest extends AbstractTestSyncAPMHandler
{
    public function testPayWithPayerActionRequiredConfirmsPaymentSourceAndRedirects(): void
    {
        $systemConfig = $this->createDefaultSystemConfig([Settings::SPB_APP_SWITCH_ENABLED => true]);
        $orderResource = new OrderResource(self::orderGateway(), new ApiContextFactoryMock());
        $handler = new PayLaterHandler(
            new SettingsValidationService($systemConfig, new NullLogger()),
            $this->stateMachineRegistry,
            new OrderExecuteService(
                $orderResource,
                new OrderTransactionStateHandler($this->stateMachineRegistry),
                new OrderNumberPatchBuilder(),
                new NullLogger(),
            ),
            new OrderPatchService($systemConfig, $this->getContainer()->get(PurchaseUnitPatchBuilder::class), $orderResource),
            new TransactionDataService(
                $this->orderTransactionRepo,
                new CredentialsUtil($systemConfig),
                static::createStub(OrderTransactionService::class),
            ),
            $orderResource,
            static::createStub(VaultTokenService::class),
            $this->orderTransactionRepo,
            $this->createOrderBuilder($systemConfig),
        );
        $context = Context::createDefaultContext();
        $transactionId = $this->getTransactionId($context, $this->getContainer());
        $returnUrl = 'https://example.com/payment/finalize-transaction?_sw_payment_token=testToken';

        $response = $handler->pay(new Request([], [
            AbstractPaymentMethodHandler::PAYPAL_PAYMENT_ORDER_ID_INPUT_NAME => MockRequestHandler::PAYPAL_ORDER_ID_PAYER_ACTION_REQUIRED,
            'product' => 'spb',
            CreateOrderRoute::PAYPAL_BUYER_USER_AGENT => 'Mozilla/5.0 (iPhone; CPU iPhone OS 17_5 like Mac OS X)',
        ]), new PaymentTransactionStruct($transactionId, $returnUrl), $context, null);

        static::assertNotNull($response);
        static::assertSame(MockRequestHandler::CONFIRMED_PAYER_ACTION_URL, $response->getTargetUrl());

        $postRequests = \array_values(\array_filter(
            self::getClient()->getAll(),
            static fn (TestRequestContext $request) => $request->getRequest()->getMethod() === 'POST' && $request->getGatewayMethod() !== 'getToken',
        ));
        static::assertSame(['captureOrder', 'confirmPaymentSource'], \array_map(
            static fn (TestRequestContext $request) => $request->getGatewayMethod(),
            $postRequests,
        ));
        static::assertStringEndsWith(
            '/' . MockRequestHandler::PAYPAL_ORDER_ID_PAYER_ACTION_REQUIRED . '/confirm-payment-source',
            (string) $postRequests[1]->getRequest()->getUri(),
        );

        $paypalSource = $postRequests[1]->getRequestBody()['payment_source']['paypal'] ?? null;
        static::assertIsArray($paypalSource);
        static::assertSame($returnUrl, $paypalSource['experience_context']['return_url'] ?? null);
        static::assertSame($returnUrl . '&cancel=1', $paypalSource['experience_context']['cancel_url'] ?? null);
        static::assertNull($paypalSource['experience_context']['app_switch_context'] ?? null);
        static::assertArrayNotHasKey('vault_id', $paypalSource);
        static::assertArrayNotHasKey('vault', $paypalSource['attributes'] ?? []);

        $transaction = $this->getTransaction($transactionId, $this->getContainer(), $context);
        static::assertNotNull($transaction);
        static::assertSame(
            MockRequestHandler::PAYPAL_ORDER_ID_PAYER_ACTION_REQUIRED,
            $transaction->getCustomFieldsValue(SwagPayPal::ORDER_TRANSACTION_CUSTOM_FIELDS_PAYPAL_ORDER_ID),
        );
        $this->assertOrderTransactionState(OrderTransactionStates::STATE_UNCONFIRMED, $transactionId, $context);
    }

    protected function getPaymentHandlerClassName(): string
    {
        return PayLaterHandler::class;
    }
}
