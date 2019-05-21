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
use Shopware\Core\Checkout\Order\OrderCollection;
use Shopware\Core\Checkout\Order\OrderEntity;
use Shopware\Core\Checkout\Payment\Cart\AsyncPaymentTransactionStruct;
use Shopware\Core\Checkout\Payment\Exception\InvalidOrderException;
use Shopware\Core\Framework\Context;
use Shopware\Core\Framework\DataAbstractionLayer\EntityRepositoryInterface;
use Shopware\Core\Framework\DataAbstractionLayer\Exception\InconsistentCriteriaIdsException;
use Shopware\Core\Framework\DataAbstractionLayer\Search\Criteria;
use Shopware\Core\System\Currency\CurrencyEntity;
use Shopware\Core\System\SalesChannel\SalesChannelContext;
use Swag\PayPal\Payment\Service\TransactionValidator;
use Swag\PayPal\PayPal\Api\Payment;
use Swag\PayPal\PayPal\Api\Payment\Transaction;
use Swag\PayPal\PayPal\Api\Payment\Transaction\ItemList;
use Swag\PayPal\PayPal\Api\Payment\Transaction\ItemList\Item;
use Swag\PayPal\Setting\Exception\PayPalSettingsInvalidException;
use Swag\PayPal\Setting\Service\SettingsServiceInterface;
use Swag\PayPal\Util\LocaleCodeProvider;

class OrderPaymentBuilder extends AbstractPaymentBuilder implements OrderPaymentBuilderInterface
{
    /**
     * @var EntityRepositoryInterface
     */
    private $orderRepository;

    public function __construct(
        SettingsServiceInterface $settingsService,
        EntityRepositoryInterface $salesChannelRepo,
        LocaleCodeProvider $localeCodeProvider,
        EntityRepositoryInterface $orderRepository
    ) {
        parent::__construct($settingsService, $salesChannelRepo, $localeCodeProvider);
        $this->orderRepository = $orderRepository;
    }

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
        $transaction = $this->createTransaction($paymentTransaction, $salesChannelContext->getContext());
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
     * @throws InconsistentCriteriaIdsException
     */
    private function createTransaction(
        AsyncPaymentTransactionStruct $paymentTransaction,
        Context $context
    ): Transaction {
        $orderTransaction = $paymentTransaction->getOrderTransaction();
        $order = $paymentTransaction->getOrder();

        $orderTransactionAmount = $orderTransaction->getAmount();

        $currencyEntity = $order->getCurrency();
        if (!$currencyEntity) {
            $currencyEntity = $this->getCurrencyFromOrderId($order->getId(), $context);
        }

        $currency = (string) $currencyEntity->getIsoCode();

        $transaction = new Transaction();

        $amount = $this->createAmount($orderTransactionAmount, $order->getShippingCosts()->getTotalPrice(), $currency);
        $transaction->setAmount($amount);

        if ($this->settings->getSendOrderNumber()) {
            $orderNumberPrefix = (string) $this->settings->getOrderNumberPrefix();
            $orderNumber = $orderNumberPrefix . $order->getOrderNumber();
            $transaction->setInvoiceNumber($orderNumber);
        }

        $itemListValid = true;
        if ($this->settings->getSubmitCart()) {
            $items = $this->getItemList($order, $currency);

            if (!empty($items)) {
                $itemList = new ItemList();
                $itemList->setItems($items);
                $transaction->setItemList($itemList);
            }
            $itemListValid = TransactionValidator::validateItemList([$transaction]);
        }

        if ($itemListValid === false) {
            $transaction->setItemList(null);
        }

        return $transaction;
    }

    /**
     * @throws InvalidOrderException
     *
     * @return Item[]
     */
    private function getItemList(
        OrderEntity $order,
        string $currency
    ): array {
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
        $item = new Item();
        $item->setName($lineItem->getLabel());
        $item->setSku($lineItem->getPayload()['productNumber']);
        $item->setCurrency($currency);
        $item->setQuantity($lineItem->getQuantity());
        $item->setTax($this->formatPrice(0));
        $item->setPrice($this->formatPrice($price->getTotalPrice() / $lineItem->getQuantity()));

        return $item;
    }

    /**
     * @throws InconsistentCriteriaIdsException
     * @throws InvalidOrderException
     */
    private function getCurrencyFromOrderId(string $orderId, Context $context): CurrencyEntity
    {
        $criteria = new Criteria([$orderId]);
        $criteria->addAssociation('currency');

        /** @var OrderCollection $orderCollection */
        $orderCollection = $this->orderRepository->search($criteria, $context);

        $order = $orderCollection->get($orderId);
        if (!$order) {
            throw new InvalidOrderException($orderId);
        }

        $currency = $order->getCurrency();
        if (!$currency) {
            throw new InvalidOrderException($orderId);
        }

        return $currency;
    }
}
