<?php declare(strict_types=1);

namespace Swag\PayPal\Payment\Builder;

use Shopware\Core\Checkout\Cart\Cart;
use Shopware\Core\Checkout\Cart\LineItem\LineItem;
use Shopware\Core\Checkout\Cart\LineItem\LineItemCollection;
use Shopware\Core\Checkout\Cart\Price\Struct\CalculatedPrice;
use Shopware\Core\Checkout\Payment\Exception\InvalidTransactionException;
use Shopware\Core\Framework\DataAbstractionLayer\Exception\InconsistentCriteriaIdsException;
use Shopware\Core\System\Currency\CurrencyEntity;
use Shopware\Core\System\SalesChannel\SalesChannelContext;
use Swag\PayPal\PayPal\Api\Payment;
use Swag\PayPal\PayPal\Api\Payment\Transaction;
use Swag\PayPal\PayPal\Api\Payment\Transaction\ItemList;
use Swag\PayPal\PayPal\Api\Payment\Transaction\ItemList\Item;
use Swag\PayPal\Setting\Exception\PayPalSettingsInvalidException;

class CartPaymentBuilder extends AbstractPaymentBuilder implements CartPaymentBuilderInterface
{
    /**
     * @throws InconsistentCriteriaIdsException
     * @throws InvalidTransactionException
     * @throws PayPalSettingsInvalidException
     */
    public function getPayment(
        Cart $cart,
        SalesChannelContext $salesChannelContext,
        string $finishUrl,
        bool $isExpressCheckoutProcess = false
    ): Payment {
        $this->settings = $this->settingsService->getSettings($salesChannelContext->getSalesChannel()->getId());

        $intent = $this->getIntent();
        $payer = $this->createPayer();
        $redirectUrls = $this->createRedirectUrls($finishUrl);
        $transaction = $this->createTransactionFromCart($cart, $salesChannelContext->getCurrency(), $isExpressCheckoutProcess);
        $applicationContext = $this->getApplicationContext($salesChannelContext);

        $requestPayment = new Payment();
        $requestPayment->setIntent($intent);
        $requestPayment->setPayer($payer);
        $requestPayment->setRedirectUrls($redirectUrls);
        $requestPayment->setTransactions([$transaction]);
        $requestPayment->setApplicationContext($applicationContext);

        return $requestPayment;
    }

    /**
     * @throws InvalidTransactionException
     */
    private function createTransactionFromCart(
        Cart $cart,
        CurrencyEntity $currencyEntity,
        bool $isExpressCheckoutProcess
    ): Transaction {
        $transaction = $cart->getTransactions()->first();
        if ($transaction === null) {
            throw new InvalidTransactionException('');
        }
        $transactionAmount = $transaction->getAmount();
        $currency = (string) $currencyEntity->getShortName();

        $transaction = new Transaction();
        $shippingCostsTotal = $cart->getDeliveries()->getShippingCosts()->sum()->getTotalPrice();
        $amount = $this->createAmount($transactionAmount, $shippingCostsTotal, $currency);
        $transaction->setAmount($amount);

        // If its an express checkout process, use the ecs submit cart option
        if ($isExpressCheckoutProcess && $this->settings->getEcsSubmitCart()) {
            $this->setItemList($transaction, $cart->getLineItems(), $currency);
        }

        // If its not an express checkout process, use the normal submit cart option
        if (!$isExpressCheckoutProcess && $this->settings->getSubmitCart()) {
            $this->setItemList($transaction, $cart->getLineItems(), $currency);
        }

        return $transaction;
    }

    /**
     * @return Item[]
     */
    private function getItemList(
        LineItemCollection $lineItemCollection,
        string $currency
    ): array {
        $items = [];

        /** @var LineItem[] $lineItems */
        $lineItems = $lineItemCollection->getElements();

        foreach ($lineItems as $id => $lineItem) {
            $price = $lineItem->getPrice();

            if ($price === null) {
                return [];
            }

            $items[] = $this->createItemFromLineItem($lineItem, $currency, $price);
        }

        return $items;
    }

    private function createItemFromLineItem(LineItem $lineItem, string $currency, CalculatedPrice $price): Item
    {
        $taxAmount = $price->getCalculatedTaxes()->getAmount();

        $item = new Item();
        $item->setName((string) $lineItem->getLabel());
        $item->setSku($lineItem->getPayload()['id']);
        $item->setPrice($this->formatPrice($price->getTotalPrice() - $taxAmount));
        $item->setCurrency($currency);
        $item->setQuantity($lineItem->getQuantity());
        $item->setTax($this->formatPrice($taxAmount));

        return $item;
    }

    private function setItemList(Transaction $transaction, LineItemCollection $lineItemCollection, string $currency): void
    {
        $items = $this->getItemList($lineItemCollection, $currency);

        if (!empty($items)) {
            $itemList = new ItemList();
            $itemList->setItems($items);
            $transaction->setItemList($itemList);
        }
    }
}
