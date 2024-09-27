<?php declare(strict_types=1);
/*
 * (c) shopware AG <info@shopware.com>
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Swag\PayPal\PaymentsApi\Builder;

use Psr\Log\LoggerInterface;
use Shopware\Core\Checkout\Order\Aggregate\OrderLineItem\OrderLineItemEntity;
use Shopware\Core\Checkout\Order\OrderEntity;
use Shopware\Core\Checkout\Payment\Cart\AsyncPaymentTransactionStruct;
use Shopware\Core\Framework\Context;
use Shopware\Core\Framework\DataAbstractionLayer\EntityRepository;
use Shopware\Core\Framework\DataAbstractionLayer\Search\Criteria;
use Shopware\Core\Framework\Log\Package;
use Shopware\Core\System\Currency\CurrencyCollection;
use Shopware\Core\System\Currency\CurrencyEntity;
use Shopware\Core\System\SalesChannel\SalesChannelContext;
use Shopware\Core\System\SystemConfig\SystemConfigService;
use Swag\PayPal\Checkout\Exception\CurrencyNotFoundException;
use Swag\PayPal\PaymentsApi\Builder\Event\PayPalV1ItemFromOrderEvent;
use Swag\PayPal\PaymentsApi\Builder\Util\AmountProvider;
use Swag\PayPal\PaymentsApi\Service\TransactionValidator;
use Swag\PayPal\RestApi\V1\Api\Payment;
use Swag\PayPal\RestApi\V1\Api\Payment\Transaction;
use Swag\PayPal\RestApi\V1\Api\Payment\Transaction\ItemList;
use Swag\PayPal\RestApi\V1\Api\Payment\Transaction\ItemList\Item;
use Swag\PayPal\RestApi\V1\Api\Payment\Transaction\ItemList\ItemCollection;
use Swag\PayPal\RestApi\V1\Api\Payment\TransactionCollection;
use Swag\PayPal\Setting\Settings;
use Swag\PayPal\Util\LocaleCodeProvider;
use Swag\PayPal\Util\PriceFormatter;
use Symfony\Contracts\EventDispatcher\EventDispatcherInterface;

#[Package('checkout')]
class OrderPaymentBuilder extends AbstractPaymentBuilder implements OrderPaymentBuilderInterface
{
    private EntityRepository $currencyRepository;

    /**
     * @internal
     */
    public function __construct(
        LocaleCodeProvider $localeCodeProvider,
        PriceFormatter $priceFormatter,
        EventDispatcherInterface $eventDispatcher,
        LoggerInterface $logger,
        SystemConfigService $systemConfigService,
        EntityRepository $currencyRepository,
    ) {
        parent::__construct($localeCodeProvider, $priceFormatter, $eventDispatcher, $logger, $systemConfigService);
        $this->currencyRepository = $currencyRepository;
    }

    /**
     * {@inheritdoc}
     */
    public function getPayment(
        AsyncPaymentTransactionStruct $paymentTransaction,
        SalesChannelContext $salesChannelContext,
    ): Payment {
        $payer = $this->createPayer();
        $redirectUrls = $this->createRedirectUrls($paymentTransaction->getReturnUrl());
        $transaction = $this->createTransaction($paymentTransaction, $salesChannelContext);
        $applicationContext = $this->getApplicationContext($salesChannelContext);

        $requestPayment = new Payment();
        $requestPayment->setPayer($payer);
        $requestPayment->setRedirectUrls($redirectUrls);
        $requestPayment->setTransactions(new TransactionCollection([$transaction]));
        $requestPayment->setApplicationContext($applicationContext);

        return $requestPayment;
    }

    private function createTransaction(
        AsyncPaymentTransactionStruct $paymentTransaction,
        SalesChannelContext $salesChannelContext,
    ): Transaction {
        $orderTransaction = $paymentTransaction->getOrderTransaction();
        $order = $paymentTransaction->getOrder();

        $orderTransactionAmount = $orderTransaction->getAmount();

        $currencyEntity = $order->getCurrency();
        if ($currencyEntity === null) {
            $currencyEntity = $this->getCurrency($order->getCurrencyId(), $salesChannelContext->getContext());
        }

        $currency = $currencyEntity->getIsoCode();

        $transaction = new Transaction();
        $transaction->setCustom($orderTransaction->getId());

        $amount = (new AmountProvider($this->priceFormatter))->createAmount(
            $orderTransactionAmount,
            $order->getShippingCosts()->getTotalPrice(),
            $currency
        );
        $transaction->setAmount($amount);

        if ($this->systemConfigService->getBool(Settings::SEND_ORDER_NUMBER, $salesChannelContext->getSalesChannelId())) {
            $orderNumberPrefix = $this->systemConfigService->getString(Settings::ORDER_NUMBER_PREFIX, $salesChannelContext->getSalesChannelId());
            $orderNumberSuffix = $this->systemConfigService->getString(Settings::ORDER_NUMBER_SUFFIX, $salesChannelContext->getSalesChannelId());
            $orderNumber = $orderNumberPrefix . $order->getOrderNumber() . $orderNumberSuffix;
            $transaction->setInvoiceNumber($orderNumber);
        }

        $itemListValid = true;
        if ($this->systemConfigService->getBool(Settings::SUBMIT_CART, $salesChannelContext->getSalesChannelId())) {
            $items = $this->getItemList($order, $currency);

            if ($items->count() > 0) {
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
     * @throws CurrencyNotFoundException
     */
    private function getCurrency(string $currencyId, Context $context): CurrencyEntity
    {
        $criteria = new Criteria([$currencyId]);

        /** @var CurrencyCollection $currencyCollection */
        $currencyCollection = $this->currencyRepository->search($criteria, $context)->getEntities();

        $currency = $currencyCollection->get($currencyId);
        if ($currency === null) {
            throw new CurrencyNotFoundException($currencyId);
        }

        return $currency;
    }

    private function getItemList(OrderEntity $order, string $currency): ItemCollection
    {
        $items = new ItemCollection();
        $lineItems = $order->getNestedLineItems();
        if ($lineItems === null) {
            return $items;
        }

        foreach ($lineItems->getElements() as $lineItem) {
            $items->add($this->createItemFromLineItem($lineItem, $currency));
        }

        return $items;
    }

    private function createItemFromLineItem(OrderLineItemEntity $lineItem, string $currencyCode): Item
    {
        $item = new Item();

        $this->setName($lineItem, $item);
        $this->setSku($lineItem, $item);

        $item->setCurrency($currencyCode);
        $item->setQuantity($lineItem->getQuantity());
        $item->setTax($this->priceFormatter->formatPrice(0, $currencyCode));
        $item->setPrice($this->priceFormatter->formatPrice($lineItem->getUnitPrice(), $currencyCode));

        $event = new PayPalV1ItemFromOrderEvent($item, $lineItem);
        $this->eventDispatcher->dispatch($event);

        return $event->getPayPalLineItem();
    }

    private function setName(OrderLineItemEntity $lineItem, Item $item): void
    {
        $label = $lineItem->getLabel();

        try {
            $item->setName($label);
        } catch (\LengthException $e) {
            $this->logger->warning($e->getMessage(), ['lineItem' => $lineItem]);
            $item->setName(\mb_substr($label, 0, Item::MAX_LENGTH_NAME));
        }
    }

    private function setSku(OrderLineItemEntity $lineItem, Item $item): void
    {
        $payload = $lineItem->getPayload();
        if ($payload === null || !\array_key_exists('productNumber', $payload)) {
            return;
        }

        $productNumber = $payload['productNumber'];

        try {
            $item->setSku($productNumber);
        } catch (\LengthException $e) {
            $this->logger->warning($e->getMessage(), ['lineItem' => $lineItem]);
            $item->setSku(\mb_substr($productNumber, 0, Item::MAX_LENGTH_SKU));
        }
    }
}
