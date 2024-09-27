<?php declare(strict_types=1);
/*
 * (c) shopware AG <info@shopware.com>
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Swag\PayPal\PaymentsApi\Patch;

use Shopware\Core\Checkout\Payment\Cart\AsyncPaymentTransactionStruct;
use Shopware\Core\Framework\Log\Package;
use Shopware\Core\System\SalesChannel\SalesChannelContext;
use Swag\PayPal\PaymentsApi\Builder\OrderPaymentBuilderInterface;
use Swag\PayPal\RestApi\V1\Api\Patch;

#[Package('checkout')]
class TransactionPatchBuilder
{
    private OrderPaymentBuilderInterface $orderPaymentBuilder;

    /**
     * @internal
     */
    public function __construct(OrderPaymentBuilderInterface $orderPaymentBuilder)
    {
        $this->orderPaymentBuilder = $orderPaymentBuilder;
    }

    /**
     * @return Patch[]
     */
    public function createTransactionPatch(
        AsyncPaymentTransactionStruct $paymentTransaction,
        SalesChannelContext $salesChannelContext,
    ): array {
        $patches = [];
        $transaction = $this->orderPaymentBuilder->getPayment($paymentTransaction, $salesChannelContext)->getTransactions()->first();
        if (!$transaction) {
            return $patches;
        }

        $patches[] = (new Patch())->assign([
            'op' => Patch::OPERATION_ADD,
            'path' => '/transactions/0/custom',
            'value' => $transaction->getCustom(),
        ]);

        if ($transaction->getInvoiceNumber()) {
            $patches[] = (new Patch())->assign([
                'op' => Patch::OPERATION_ADD,
                'path' => '/transactions/0/invoice_number',
                'value' => $transaction->getInvoiceNumber(),
            ]);
        }

        $amount = (new Patch())->assign([
            'op' => Patch::OPERATION_REPLACE,
            'path' => '/transactions/0/amount',
        ]);
        $amount->setValue(\json_decode((string) \json_encode($transaction->getAmount()), true));
        $patches[] = $amount;

        if ($transaction->getItemList() !== null) {
            $itemList = new Patch();
            $itemList->assign([
                'op' => Patch::OPERATION_REPLACE,
                'path' => '/transactions/0/item_list/items',
            ]);
            $itemList->setValue(\json_decode((string) \json_encode($transaction->getItemList()->getItems()), true));
            $patches[] = $itemList;
        }

        return $patches;
    }
}
