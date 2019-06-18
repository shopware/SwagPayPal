<?php declare(strict_types=1);

namespace Swag\PayPal\Payment\Service;

use Swag\PayPal\PayPal\Api\Payment\Transaction;
use Swag\PayPal\PayPal\Api\Payment\Transaction\ItemList;
use Swag\PayPal\PayPal\Api\Payment\Transaction\ItemList\Item;

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
        $transactionValid = [];

        /** @var Transaction $transaction */
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
            /** @var Item $item */
            foreach ($itemList->getItems() as $item) {
                $quantity = $item->getQuantity();

                $itemPrices[] = (float) $item->getPrice() * $quantity;
                $itemTaxes[] = (float) $item->getTax() * $quantity;
            }
            $transactionValid[] = ((string) array_sum($itemPrices) === (string) $subTotal) && ((string) array_sum($itemTaxes) === (string) $totalTax);
        }

        return $transactionValid === array_filter($transactionValid);
    }
}
