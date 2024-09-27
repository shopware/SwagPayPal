<?php declare(strict_types=1);
/*
 * (c) shopware AG <info@shopware.com>
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Swag\PayPal\PaymentsApi\Builder;

use Psr\Log\LoggerInterface;
use Shopware\Core\Checkout\Cart\Cart;
use Shopware\Core\Checkout\Cart\LineItem\LineItem;
use Shopware\Core\Checkout\Cart\LineItem\LineItemCollection;
use Shopware\Core\Checkout\Cart\Price\Struct\CalculatedPrice;
use Shopware\Core\Checkout\Payment\PaymentException;
use Shopware\Core\Framework\Log\Package;
use Shopware\Core\System\SalesChannel\SalesChannelContext;
use Shopware\Core\System\SystemConfig\SystemConfigService;
use Swag\PayPal\PaymentsApi\Builder\Event\PayPalV1ItemFromCartEvent;
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
class CartPaymentBuilder extends AbstractPaymentBuilder implements CartPaymentBuilderInterface
{
    /**
     * @internal
     */
    public function __construct(
        LocaleCodeProvider $localeCodeProvider,
        PriceFormatter $priceFormatter,
        EventDispatcherInterface $eventDispatcher,
        LoggerInterface $logger,
        SystemConfigService $systemConfigService,
    ) {
        parent::__construct($localeCodeProvider, $priceFormatter, $eventDispatcher, $logger, $systemConfigService);
    }

    public function getPayment(
        Cart $cart,
        SalesChannelContext $salesChannelContext,
        string $finishUrl,
        bool $isExpressCheckoutProcess = false,
    ): Payment {
        $payer = $this->createPayer();
        $redirectUrls = $this->createRedirectUrls($finishUrl);
        $transaction = $this->createTransactionFromCart(
            $cart,
            $salesChannelContext,
            $isExpressCheckoutProcess
        );
        $applicationContext = $this->getApplicationContext($salesChannelContext);

        $requestPayment = new Payment();
        $requestPayment->setPayer($payer);
        $requestPayment->setRedirectUrls($redirectUrls);
        $requestPayment->setTransactions(new TransactionCollection([$transaction]));
        $requestPayment->setApplicationContext($applicationContext);

        return $requestPayment;
    }

    /**
     * @throws PaymentException
     */
    private function createTransactionFromCart(
        Cart $cart,
        SalesChannelContext $salesChannelContext,
        bool $isExpressCheckoutProcess,
    ): Transaction {
        $cartTransaction = $cart->getTransactions()->first();
        if ($cartTransaction === null) {
            throw PaymentException::invalidTransaction('');
        }
        $transactionAmount = $cartTransaction->getAmount();
        $currencyCode = $salesChannelContext->getCurrency()->getIsoCode();

        $transaction = new Transaction();
        $shippingCostsTotal = $cart->getShippingCosts()->getTotalPrice();
        $amount = (new AmountProvider($this->priceFormatter))->createAmount($transactionAmount, $shippingCostsTotal, $currencyCode);
        $transaction->setAmount($amount);

        $itemListValid = true;
        if ($this->systemConfigService->getBool(Settings::SUBMIT_CART, $salesChannelContext->getSalesChannelId())) {
            $this->setItemList($transaction, $cart->getLineItems(), $currencyCode);
            $itemListValid = TransactionValidator::validateItemList([$transaction]);
        }

        if ($itemListValid === false) {
            $transaction->setItemList(null);
        }

        return $transaction;
    }

    private function setItemList(
        Transaction $transaction,
        LineItemCollection $lineItemCollection,
        string $currency,
    ): void {
        $items = $this->getItemList($lineItemCollection, $currency);

        if ($items->count() > 0) {
            $itemList = new ItemList();
            $itemList->setItems($items);
            $transaction->setItemList($itemList);
        }
    }

    private function getItemList(
        LineItemCollection $lineItemCollection,
        string $currency,
    ): ItemCollection {
        $items = new ItemCollection();

        foreach ($lineItemCollection->getElements() as $lineItem) {
            $price = $lineItem->getPrice();

            if ($price === null) {
                continue;
            }

            $items->add($this->createItemFromLineItem($lineItem, $currency, $price));
        }

        return $items;
    }

    private function createItemFromLineItem(
        LineItem $lineItem,
        string $currencyCode,
        CalculatedPrice $price,
    ): Item {
        $item = new Item();
        $this->setName($lineItem, $item);
        $this->setSku($lineItem, $item);

        $item->setCurrency($currencyCode);
        $item->setQuantity($lineItem->getQuantity());
        $item->setPrice($this->priceFormatter->formatPrice($price->getUnitPrice(), $currencyCode));
        $item->setTax($this->priceFormatter->formatPrice(0, $currencyCode));

        $event = new PayPalV1ItemFromCartEvent($item, $lineItem);
        $this->eventDispatcher->dispatch($event);

        return $event->getPayPalLineItem();
    }

    private function setName(LineItem $lineItem, Item $item): void
    {
        $label = (string) $lineItem->getLabel();

        try {
            $item->setName($label);
        } catch (\LengthException $e) {
            $this->logger->warning($e->getMessage(), ['lineItem' => $lineItem]);
            $item->setName(\mb_substr($label, 0, Item::MAX_LENGTH_NAME));
        }
    }

    private function setSku(LineItem $lineItem, Item $item): void
    {
        $productNumber = $lineItem->getPayloadValue('productNumber');

        try {
            $item->setSku($productNumber);
        } catch (\LengthException $e) {
            $this->logger->warning($e->getMessage(), ['lineItem' => $lineItem]);
            $item->setSku(\mb_substr($productNumber, 0, Item::MAX_LENGTH_SKU));
        }
    }
}
