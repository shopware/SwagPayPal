<?php declare(strict_types=1);
/*
 * (c) shopware AG <info@shopware.com>
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Swag\PayPal\PaymentsApi\Service;

use Shopware\Core\Framework\Log\Package;
use Swag\PayPal\RestApi\V1\Api\Payment\Transaction;

#[Package('checkout')]
class TransactionValidator
{
    /**
     * Returns true if the ItemList matches the Amount.
     * Returns false if the ItemList does not match the Amount.
     *
     * @param Transaction[] $transactions
     */
    public static function validateItemList(array $transactions): bool
    {
        /** @var array<array-key, true> $transactionValid */
        $transactionValid = [];

        foreach ($transactions as $transaction) {
            $itemList = $transaction->getItemList();

            if (!$itemList) {
                return false;
            }

            $details = $transaction->getAmount()->getDetails();
            $subTotal = (float) $details->getSubtotal();
            $totalTax = (float) $details->getTax();

            $itemPrices = [];
            $itemTaxes = [];
            foreach ($itemList->getItems() as $item) {
                $quantity = $item->getQuantity();

                $itemPrices[] = (float) $item->getPrice() * $quantity;
                $itemTaxes[] = (float) $item->getTax() * $quantity;
            }
            $transactionValid[] = ((string) \array_sum($itemPrices) === (string) $subTotal) && ((string) \array_sum($itemTaxes) === (string) $totalTax);
        }

        return $transactionValid === \array_filter($transactionValid);
    }
}
