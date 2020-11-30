<?php declare(strict_types=1);
/*
 * (c) shopware AG <info@shopware.com>
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Swag\PayPal\OrdersApi\Builder;

use Psr\Log\LoggerInterface;
use Shopware\Core\Checkout\Cart\Cart;
use Shopware\Core\Checkout\Cart\LineItem\LineItem;
use Shopware\Core\Checkout\Customer\CustomerEntity;
use Shopware\Core\Checkout\Payment\Exception\InvalidTransactionException;
use Shopware\Core\System\Currency\CurrencyEntity;
use Shopware\Core\System\SalesChannel\SalesChannelContext;
use Swag\PayPal\OrdersApi\Builder\Event\PayPalItemFromCartEvent;
use Swag\PayPal\OrdersApi\Builder\Util\AmountProvider;
use Swag\PayPal\RestApi\V2\Api\Order;
use Swag\PayPal\RestApi\V2\Api\Order\PurchaseUnit;
use Swag\PayPal\RestApi\V2\Api\Order\PurchaseUnit\Item;
use Swag\PayPal\RestApi\V2\Api\Order\PurchaseUnit\Item\UnitAmount;
use Swag\PayPal\Setting\Service\SettingsServiceInterface;
use Swag\PayPal\Setting\SwagPayPalSettingStruct;
use Swag\PayPal\Util\PriceFormatter;
use Symfony\Contracts\EventDispatcher\EventDispatcherInterface;

class OrderFromCartBuilder extends AbstractOrderBuilder
{
    /**
     * @var EventDispatcherInterface
     */
    private $eventDispatcher;

    /**
     * @var LoggerInterface
     */
    private $logger;

    public function __construct(
        SettingsServiceInterface $settingsService,
        PriceFormatter $priceFormatter,
        AmountProvider $amountProvider,
        EventDispatcherInterface $eventDispatcher,
        LoggerInterface $logger
    ) {
        parent::__construct($settingsService, $priceFormatter, $amountProvider);
        $this->eventDispatcher = $eventDispatcher;
        $this->logger = $logger;
    }

    public function getOrder(
        Cart $cart,
        SalesChannelContext $salesChannelContext,
        ?CustomerEntity $customer,
        bool $isExpressCheckout = false
    ): Order {
        $settings = $this->settingsService->getSettings($salesChannelContext->getSalesChannel()->getId());
        $order = new Order();

        $intent = $this->getIntent($settings);
        if ($customer !== null) {
            $payer = $this->createPayer($customer);
            $order->setPayer($payer);
        }
        $purchaseUnit = $this->createPurchaseUnit($salesChannelContext, $cart, $customer, $settings, $isExpressCheckout);
        $applicationContext = $this->createApplicationContext($salesChannelContext, $settings);

        $order->setIntent($intent);
        $order->setPurchaseUnits([$purchaseUnit]);
        $order->setApplicationContext($applicationContext);

        return $order;
    }

    private function createPurchaseUnit(
        SalesChannelContext $salesChannelContext,
        Cart $cart,
        ?CustomerEntity $customer,
        SwagPayPalSettingStruct $settings,
        bool $isExpressCheckoutProcess
    ): PurchaseUnit {
        $cartTransaction = $cart->getTransactions()->first();
        if ($cartTransaction === null) {
            throw new InvalidTransactionException('');
        }

        $currency = $salesChannelContext->getCurrency();
        $purchaseUnit = new PurchaseUnit();

        if (($isExpressCheckoutProcess && $settings->getEcsSubmitCart())
            || (!$isExpressCheckoutProcess && $settings->getSubmitCart())
        ) {
            $purchaseUnit->setItems($this->createItems($currency, $cart));
        }

        $amount = $this->amountProvider->createAmount(
            $cartTransaction->getAmount(),
            $cart->getShippingCosts(),
            $currency,
            $purchaseUnit
        );

        if ($customer !== null) {
            $shipping = $this->createShipping($customer);
            $purchaseUnit->setShipping($shipping);
        }

        $purchaseUnit->setAmount($amount);

        return $purchaseUnit;
    }

    /**
     * @return Item[]
     */
    private function createItems(CurrencyEntity $currency, Cart $cart): array
    {
        $items = [];
        $currencyCode = $currency->getIsoCode();

        foreach ($cart->getLineItems() as $lineItem) {
            $price = $lineItem->getPrice();

            if ($price === null) {
                continue;
            }

            $item = new Item();
            $this->setLabel($lineItem, $item);
            $this->setSku($lineItem, $item);

            $unitAmount = new UnitAmount();
            $unitAmount->setCurrencyCode($currencyCode);
            $unitAmount->setValue($this->priceFormatter->formatPrice($price->getUnitPrice()));

            $item->setUnitAmount($unitAmount);
            $item->setQuantity($lineItem->getQuantity());

            $event = new PayPalItemFromCartEvent($item, $lineItem);
            $this->eventDispatcher->dispatch($event);

            $items[] = $event->getPaypalLineItem();
        }

        return $items;
    }

    private function setLabel(LineItem $lineItem, Item $item): void
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
        $payload = $lineItem->getPayload();
        $productNumber = $payload['productNumber'] ?? null;

        try {
            $item->setSku($productNumber);
        } catch (\LengthException $e) {
            $this->logger->warning($e->getMessage(), ['lineItem' => $lineItem]);
            $item->setSku(\substr($productNumber, 0, Item::MAX_LENGTH_SKU));
        }
    }
}
