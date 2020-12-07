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
use Shopware\Core\Checkout\Payment\Exception\InvalidTransactionException;
use Shopware\Core\System\Currency\CurrencyEntity;
use Shopware\Core\System\SalesChannel\SalesChannelContext;
use Swag\PayPal\PaymentsApi\Builder\Event\PayPalV1ItemFromCartEvent;
use Swag\PayPal\PaymentsApi\Builder\Util\AmountProvider;
use Swag\PayPal\PaymentsApi\Service\TransactionValidator;
use Swag\PayPal\RestApi\V1\Api\Payment;
use Swag\PayPal\RestApi\V1\Api\Payment\Transaction;
use Swag\PayPal\RestApi\V1\Api\Payment\Transaction\ItemList;
use Swag\PayPal\RestApi\V1\Api\Payment\Transaction\ItemList\Item;
use Swag\PayPal\Setting\Service\SettingsServiceInterface;
use Swag\PayPal\Util\LocaleCodeProvider;
use Swag\PayPal\Util\PriceFormatter;
use Symfony\Contracts\EventDispatcher\EventDispatcherInterface;

class CartPaymentBuilder extends AbstractPaymentBuilder implements CartPaymentBuilderInterface
{
    public function __construct(
        SettingsServiceInterface $settingsService,
        LocaleCodeProvider $localeCodeProvider,
        PriceFormatter $priceFormatter,
        EventDispatcherInterface $eventDispatcher,
        LoggerInterface $logger
    ) {
        parent::__construct($settingsService, $localeCodeProvider, $priceFormatter, $eventDispatcher, $logger);
    }

    public function getPayment(
        Cart $cart,
        SalesChannelContext $salesChannelContext,
        string $finishUrl,
        bool $isExpressCheckoutProcess = false
    ): Payment {
        $this->settings = $this->settingsService->getSettings($salesChannelContext->getSalesChannel()->getId());

        $payer = $this->createPayer();
        $redirectUrls = $this->createRedirectUrls($finishUrl);
        $transaction = $this->createTransactionFromCart(
            $cart,
            $salesChannelContext->getCurrency(),
            $isExpressCheckoutProcess
        );
        $applicationContext = $this->getApplicationContext($salesChannelContext);

        $requestPayment = new Payment();
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
        $cartTransaction = $cart->getTransactions()->first();
        if ($cartTransaction === null) {
            throw new InvalidTransactionException('');
        }
        $transactionAmount = $cartTransaction->getAmount();
        $currency = $currencyEntity->getIsoCode();

        $transaction = new Transaction();
        $shippingCostsTotal = $cart->getShippingCosts()->getTotalPrice();
        $amount = (new AmountProvider($this->priceFormatter))->createAmount($transactionAmount, $shippingCostsTotal, $currency);
        $transaction->setAmount($amount);

        $itemListValid = true;
        // If its an express checkout process, use the ecs submit cart option
        if ($isExpressCheckoutProcess && $this->settings->getEcsSubmitCart()) {
            $this->setItemList($transaction, $cart->getLineItems(), $currency);
            $itemListValid = TransactionValidator::validateItemList([$transaction]);
        }

        // If its not an express checkout process, use the normal submit cart option
        if (!$isExpressCheckoutProcess && $this->settings->getSubmitCart()) {
            $this->setItemList($transaction, $cart->getLineItems(), $currency);
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
        string $currency
    ): void {
        $items = $this->getItemList($lineItemCollection, $currency);

        if (!empty($items)) {
            $itemList = new ItemList();
            $itemList->setItems($items);
            $transaction->setItemList($itemList);
        }
    }

    /**
     * @return Item[]
     */
    private function getItemList(
        LineItemCollection $lineItemCollection,
        string $currency
    ): array {
        $items = [];

        foreach ($lineItemCollection->getElements() as $lineItem) {
            $price = $lineItem->getPrice();

            if ($price === null) {
                continue;
            }

            $items[] = $this->createItemFromLineItem($lineItem, $currency, $price);
        }

        return $items;
    }

    private function createItemFromLineItem(
        LineItem $lineItem,
        string $currency,
        CalculatedPrice $price
    ): Item {
        $item = new Item();
        $this->setName($lineItem, $item);
        $this->setSku($lineItem, $item);

        $item->setCurrency($currency);
        $item->setQuantity($lineItem->getQuantity());
        $item->setPrice($this->priceFormatter->formatPrice($price->getUnitPrice()));
        $item->setTax($this->priceFormatter->formatPrice(0));

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
            $item->setName(\substr($label, 0, Item::MAX_LENGTH_NAME));
        }
    }

    private function setSku(LineItem $lineItem, Item $item): void
    {
        $productNumber = $lineItem->getPayloadValue('productNumber');

        try {
            $item->setSku($productNumber);
        } catch (\LengthException $e) {
            $this->logger->warning($e->getMessage(), ['lineItem' => $lineItem]);
            $item->setSku(\substr($productNumber, 0, Item::MAX_LENGTH_SKU));
        }
    }
}
