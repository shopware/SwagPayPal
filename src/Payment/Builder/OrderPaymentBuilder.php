<?php declare(strict_types=1);
/**
 * (c) shopware AG <info@shopware.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Swag\PayPal\Payment\Builder;

use Shopware\Core\Checkout\Cart\Price\Struct\CalculatedPrice;
use Shopware\Core\Checkout\Order\Aggregate\OrderLineItem\OrderLineItemEntity;
use Shopware\Core\Checkout\Order\OrderEntity;
use Shopware\Core\Checkout\Payment\Cart\AsyncPaymentTransactionStruct;
use Shopware\Core\Checkout\Payment\Exception\InvalidOrderException;
use Shopware\Core\Framework\DataAbstractionLayer\Exception\InconsistentCriteriaIdsException;
use Shopware\Core\System\SalesChannel\SalesChannelContext;
use Swag\PayPal\PayPal\Api\Payment;
use Swag\PayPal\PayPal\Api\Payment\Transaction;
use Swag\PayPal\PayPal\Api\Payment\Transaction\ItemList;
use Swag\PayPal\PayPal\Api\Payment\Transaction\ItemList\Item;
use Swag\PayPal\Setting\Exception\PayPalSettingsInvalidException;

class OrderPaymentBuilder extends AbstractPaymentBuilder implements OrderPaymentBuilderInterface
{
    /**
     * {@inheritdoc}
     *
     * @throws InconsistentCriteriaIdsException
     * @throws InvalidOrderException
     * @throws PayPalSettingsInvalidException
     */
    public function getPayment(
        AsyncPaymentTransactionStruct $paymentTransaction,
        SalesChannelContext $salesChannelContext
    ): Payment {
        $this->settings = $this->settingsService->getSettings($salesChannelContext->getSalesChannel()->getId());

        $intent = $this->getIntent();
        $payer = $this->createPayer();
        $redirectUrls = $this->createRedirectUrls($paymentTransaction->getReturnUrl());
        $transaction = $this->createTransaction($paymentTransaction);
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
     * @throws InvalidOrderException
     */
    private function createTransaction(AsyncPaymentTransactionStruct $paymentTransaction): Transaction
    {
        $orderTransaction = $paymentTransaction->getOrderTransaction();
        $order = $paymentTransaction->getOrder();

        $orderTransactionAmount = $orderTransaction->getAmount();
        $currency = (string) $order->getCurrency()->getShortName();

        $transaction = new Transaction();

        $amount = $this->createAmount($orderTransactionAmount, $order->getShippingCosts()->getTotalPrice(), $currency);
        $transaction->setAmount($amount);

        if ($this->settings->getSendOrderNumber()) {
            $orderNumberPrefix = (string) $this->settings->getOrderNumberPrefix();
            $orderNumber = $orderNumberPrefix . $order->getOrderNumber();
            $transaction->setInvoiceNumber($orderNumber);
        }

        if ($this->settings->getSubmitCart()) {
            $items = $this->getItemList($order, $currency);

            if (!empty($items)) {
                $itemList = new ItemList();
                $itemList->setItems($items);
                $transaction->setItemList($itemList);
            }
        }

        return $transaction;
    }

    /**
     * @throws InvalidOrderException
     *
     * @return Item[]
     */
    private function getItemList(OrderEntity $order, string $currency): array
    {
        $items = [];
        if ($order->getLineItems() === null) {
            throw new InvalidOrderException($order->getId());
        }

        /** @var OrderLineItemEntity[] $lineItems */
        $lineItems = $order->getLineItems()->getElements();

        foreach ($lineItems as $id => $lineItem) {
            $price = $lineItem->getPrice();

            if ($price === null) {
                return [];
            }

            $items[] = $this->createItemFromLineItem($lineItem, $currency, $price);
        }

        return $items;
    }

    private function createItemFromLineItem(
        OrderLineItemEntity $lineItem,
        string $currency,
        CalculatedPrice $price
    ): Item {
        $taxAmount = $price->getCalculatedTaxes()->getAmount();

        $item = new Item();
        $item->setName($lineItem->getLabel());
        $item->setSku($lineItem->getPayload()['id']);
        $item->setPrice($this->formatPrice($price->getTotalPrice() - $taxAmount));
        $item->setCurrency($currency);
        $item->setQuantity($lineItem->getQuantity());
        $item->setTax($this->formatPrice($taxAmount));

        return $item;
    }
}
