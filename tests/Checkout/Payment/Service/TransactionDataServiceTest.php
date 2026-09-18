<?php declare(strict_types=1);
/*
 * (c) shopware AG <info@shopware.com>
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Swag\PayPal\Test\Checkout\Payment\Service;

use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Shopware\Core\Checkout\Order\Aggregate\OrderTransaction\OrderTransactionCollection;
use Shopware\Core\Checkout\Order\Aggregate\OrderTransaction\OrderTransactionEntity;
use Shopware\Core\Framework\Context;
use Shopware\Core\Framework\Log\Package;
use Shopware\Core\Test\Stub\DataAbstractionLayer\StaticEntityRepository;
use Shopware\PayPalSDK\Struct\ConstantsV2;
use Shopware\PayPalSDK\Struct\V2\Order as PayPalOrder;
use Swag\PayPal\Checkout\Payment\Service\OrderTransactionService;
use Swag\PayPal\Checkout\Payment\Service\TransactionDataService;
use Swag\PayPal\Setting\Service\CredentialsUtil;
use Swag\PayPal\SwagPayPal;

/**
 * @internal
 */
#[Package('checkout')]
#[CoversClass(TransactionDataService::class)]
class TransactionDataServiceTest extends TestCase
{
    /**
     * @var StaticEntityRepository<OrderTransactionCollection>
     */
    private StaticEntityRepository $transactionRepository;

    private CredentialsUtil&MockObject $credentialsUtil;

    private OrderTransactionService&MockObject $orderTransactionService;

    private TransactionDataService $transactionDataService;

    protected function setUp(): void
    {
        $this->transactionRepository = new StaticEntityRepository([new OrderTransactionCollection()]);
        $this->credentialsUtil = $this->createMock(CredentialsUtil::class);
        $this->orderTransactionService = $this->createMock(OrderTransactionService::class);

        $this->transactionDataService = new TransactionDataService(
            $this->transactionRepository,
            $this->credentialsUtil,
            $this->orderTransactionService,
        );
    }

    #[DataProvider('dataProviderSetResourceIdWithMissingData')]
    public function testSetResourceIdWithMissingData(string $intent, array $purchaseUnits): void
    {
        $context = Context::createDefaultContext();

        $payPalOrder = (new PayPalOrder())->assign([
            'intent' => $intent,
            'purchaseUnits' => $purchaseUnits,
        ]);

        $this->transactionDataService->setResourceId($payPalOrder, 'order-transaction-id', $context);

        static::assertSame([], $this->transactionRepository->updates);
    }

    public static function dataProviderSetResourceIdWithMissingData(): \Generator
    {
        yield 'intent: capture, without purchase units' => [ConstantsV2::INTENT_CAPTURE, []];
        yield 'intent: capture, without payments' => [ConstantsV2::INTENT_CAPTURE, [['payments' => []]]];
        yield 'intent: capture, without captures' => [ConstantsV2::INTENT_CAPTURE, [['payments' => ['captures' => []]]]];

        yield 'intent: authorize, without purchase units' => [ConstantsV2::INTENT_AUTHORIZE, []];
        yield 'intent: authorize, without payments' => [ConstantsV2::INTENT_AUTHORIZE, [['payments' => []]]];
        yield 'intent: authorize, without authorizations' => [ConstantsV2::INTENT_AUTHORIZE, [['payments' => ['authorizations' => []]]]];
    }

    public function testSetResourceId(): void
    {
        $context = Context::createDefaultContext();

        $payPalOrder = (new PayPalOrder())->assign([
            'intent' => ConstantsV2::INTENT_CAPTURE,
            'purchaseUnits' => [[
                'payments' => [
                    'captures' => [['id' => 'capture-id']],
                ],
            ]],
        ]);

        $this->transactionDataService->setResourceId($payPalOrder, 'order-transaction-id', $context);

        static::assertSame([[['id' => 'order-transaction-id', 'customFields' => [
            SwagPayPal::ORDER_TRANSACTION_CUSTOM_FIELDS_PAYPAL_RESOURCE_ID => 'capture-id',
        ]]]], $this->transactionRepository->updates);
    }

    public function testCancellationRequestIsBoundToPayPalOrder(): void
    {
        $this->transactionDataService->setCancellationRequested('order-transaction-id', 'paypal-order-id', Context::createDefaultContext());

        static::assertSame([[
            [
                'id' => 'order-transaction-id',
                'customFields' => [
                    SwagPayPal::ORDER_TRANSACTION_CUSTOM_FIELDS_PAYPAL_CANCELLATION_REQUESTED => 'paypal-order-id',
                ],
            ],
        ]], $this->transactionRepository->updates);
    }

    #[DataProvider('dataProviderSetOrderId')]
    public function testSetOrderId(bool $isSandbox): void
    {
        $context = Context::createDefaultContext();

        $this->credentialsUtil
            ->expects($this->once())
            ->method('isSandbox')
            ->with('sales-channel-id')
            ->willReturn($isSandbox);

        $this->transactionDataService->setOrderId(
            'order-transaction-id',
            'paypal-order-id',
            'partner-attribution-id',
            'sales-channel-id',
            $context,
        );

        static::assertSame([[
            [
                'id' => 'order-transaction-id',
                'customFields' => [
                    SwagPayPal::ORDER_TRANSACTION_CUSTOM_FIELDS_PAYPAL_ORDER_ID => 'paypal-order-id',
                    SwagPayPal::ORDER_TRANSACTION_CUSTOM_FIELDS_PAYPAL_PARTNER_ATTRIBUTION_ID => 'partner-attribution-id',
                    SwagPayPal::ORDER_TRANSACTION_CUSTOM_FIELDS_PAYPAL_IS_SANDBOX => $isSandbox,
                    SwagPayPal::ORDER_TRANSACTION_CUSTOM_FIELDS_PAYPAL_CANCELLATION_REQUESTED => null,
                    SwagPayPal::ORDER_TRANSACTION_CUSTOM_FIELDS_PAYPAL_EXECUTION_STARTED => null,
                ],
            ],
        ]], $this->transactionRepository->updates);
    }

    #[DataProvider('existingOrderProvider')]
    public function testSetOrderIdPreservesProofOnlyForTheSameOrder(string $previousOrderId, bool $clearMarkers): void
    {
        $transaction = new OrderTransactionEntity();
        $transaction->setId('order-transaction-id');
        $transaction->setCustomFields([
            SwagPayPal::ORDER_TRANSACTION_CUSTOM_FIELDS_PAYPAL_ORDER_ID => $previousOrderId,
            SwagPayPal::ORDER_TRANSACTION_CUSTOM_FIELDS_PAYPAL_CANCELLATION_REQUESTED => $previousOrderId,
            SwagPayPal::ORDER_TRANSACTION_CUSTOM_FIELDS_PAYPAL_EXECUTION_STARTED => $previousOrderId,
        ]);
        $repository = new StaticEntityRepository([new OrderTransactionCollection([$transaction])]);
        $service = new TransactionDataService($repository, $this->credentialsUtil, $this->orderTransactionService);

        $service->setOrderId(
            'order-transaction-id',
            'paypal-order-id',
            'partner-attribution-id',
            'sales-channel-id',
            Context::createDefaultContext(),
        );

        static::assertCount(1, $repository->updates);
        $fields = $repository->updates[0][0]['customFields'];
        if ($clearMarkers) {
            static::assertArrayHasKey(SwagPayPal::ORDER_TRANSACTION_CUSTOM_FIELDS_PAYPAL_CANCELLATION_REQUESTED, $fields);
            static::assertArrayHasKey(SwagPayPal::ORDER_TRANSACTION_CUSTOM_FIELDS_PAYPAL_EXECUTION_STARTED, $fields);
            static::assertNull($fields[SwagPayPal::ORDER_TRANSACTION_CUSTOM_FIELDS_PAYPAL_CANCELLATION_REQUESTED]);
            static::assertNull($fields[SwagPayPal::ORDER_TRANSACTION_CUSTOM_FIELDS_PAYPAL_EXECUTION_STARTED]);
        } else {
            static::assertArrayNotHasKey(SwagPayPal::ORDER_TRANSACTION_CUSTOM_FIELDS_PAYPAL_CANCELLATION_REQUESTED, $fields);
            static::assertArrayNotHasKey(SwagPayPal::ORDER_TRANSACTION_CUSTOM_FIELDS_PAYPAL_EXECUTION_STARTED, $fields);
        }
    }

    public static function existingOrderProvider(): \Generator
    {
        yield 'same order keeps cancellation and submission evidence' => ['paypal-order-id', false];
        yield 'replacement order discards markers for the previous order' => ['previous-paypal-order-id', true];
    }

    public static function dataProviderSetOrderId(): \Generator
    {
        yield 'is paypal sandbox' => [true];
        yield 'is not paypal sandbox' => [false];
    }
}
