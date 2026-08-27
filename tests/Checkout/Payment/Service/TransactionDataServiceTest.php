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
use Shopware\Core\Framework\Context;
use Shopware\Core\Framework\DataAbstractionLayer\EntityRepository;
use Shopware\Core\Framework\DataAbstractionLayer\Event\EntityWrittenContainerEvent;
use Shopware\Core\Framework\Log\Package;
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
    private EntityRepository&MockObject $transactionRepository;

    private CredentialsUtil&MockObject $credentialsUtil;

    private OrderTransactionService&MockObject $orderTransactionService;

    private TransactionDataService $transactionDataService;

    protected function setUp(): void
    {
        $this->transactionRepository = $this->createMock(EntityRepository::class);
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

        $this->transactionRepository
            ->expects($this->never())
            ->method('update');

        $this->transactionDataService->setResourceId($payPalOrder, 'order-transaction-id', $context);
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

        $this->transactionRepository
            ->expects($this->once())
            ->method('update')
            ->with([[
                'id' => 'order-transaction-id',
                'customFields' => [
                    SwagPayPal::ORDER_TRANSACTION_CUSTOM_FIELDS_PAYPAL_RESOURCE_ID => 'capture-id',
                ],
            ]], $context);

        $this->transactionDataService->setResourceId($payPalOrder, 'order-transaction-id', $context);
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

        $this->transactionRepository
            ->expects($this->once())
            ->method('update')
            ->with([[
                'id' => 'order-transaction-id',
                'customFields' => [
                    SwagPayPal::ORDER_TRANSACTION_CUSTOM_FIELDS_PAYPAL_ORDER_ID => 'paypal-order-id',
                    SwagPayPal::ORDER_TRANSACTION_CUSTOM_FIELDS_PAYPAL_PARTNER_ATTRIBUTION_ID => 'partner-attribution-id',
                    SwagPayPal::ORDER_TRANSACTION_CUSTOM_FIELDS_PAYPAL_IS_SANDBOX => $isSandbox,
                ],
            ]], $context)
            ->willReturn($this->createMock(EntityWrittenContainerEvent::class));

        $this->transactionDataService->setOrderId(
            'order-transaction-id',
            'paypal-order-id',
            'partner-attribution-id',
            'sales-channel-id',
            $context,
        );
    }

    public static function dataProviderSetOrderId(): \Generator
    {
        yield 'is paypal sandbox' => [true];
        yield 'is not paypal sandbox' => [false];
    }
}
