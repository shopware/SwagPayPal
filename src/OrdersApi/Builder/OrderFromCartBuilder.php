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
use Shopware\Core\Checkout\Cart\Price\Struct\CartPrice;
use Shopware\Core\Checkout\Customer\CustomerEntity;
use Shopware\Core\Checkout\Payment\Exception\InvalidTransactionException;
use Shopware\Core\Framework\Log\Package;
use Shopware\Core\System\Currency\CurrencyEntity;
use Shopware\Core\System\SalesChannel\SalesChannelContext;
use Shopware\Core\System\SystemConfig\SystemConfigService;
use Swag\PayPal\OrdersApi\Builder\Event\PayPalV2ItemFromCartEvent;
use Swag\PayPal\OrdersApi\Builder\Util\AddressProvider;
use Swag\PayPal\OrdersApi\Builder\Util\PurchaseUnitProvider;
use Swag\PayPal\RestApi\V2\Api\Common\Money;
use Swag\PayPal\RestApi\V2\Api\Order;
use Swag\PayPal\RestApi\V2\Api\Order\PurchaseUnit;
use Swag\PayPal\RestApi\V2\Api\Order\PurchaseUnit\Item;
use Swag\PayPal\RestApi\V2\Api\Order\PurchaseUnit\ItemCollection;
use Swag\PayPal\RestApi\V2\Api\Order\PurchaseUnitCollection;
use Swag\PayPal\Setting\Settings;
use Swag\PayPal\Util\PriceFormatter;
use Symfony\Contracts\EventDispatcher\EventDispatcherInterface;

#[Package('checkout')]
class OrderFromCartBuilder extends AbstractOrderBuilder
{
    private EventDispatcherInterface $eventDispatcher;

    private LoggerInterface $logger;

    private PriceFormatter $priceFormatter;

    /**
     * @internal
     */
    public function __construct(
        PriceFormatter $priceFormatter,
        SystemConfigService $systemConfigService,
        PurchaseUnitProvider $purchaseUnitProvider,
        AddressProvider $addressProvider,
        EventDispatcherInterface $eventDispatcher,
        LoggerInterface $logger
    ) {
        parent::__construct($systemConfigService, $purchaseUnitProvider, $addressProvider);
        $this->priceFormatter = $priceFormatter;
        $this->eventDispatcher = $eventDispatcher;
        $this->logger = $logger;
    }

    public function getOrder(
        Cart $cart,
        SalesChannelContext $salesChannelContext,
        ?CustomerEntity $customer
    ): Order {
        $order = new Order();

        $intent = $this->getIntent($salesChannelContext->getSalesChannelId());
        if ($customer !== null) {
            $payer = $this->createPayer($customer);
            $order->setPayer($payer);
        }
        $purchaseUnit = $this->createPurchaseUnit($salesChannelContext, $cart, $customer);
        $applicationContext = $this->createApplicationContext($salesChannelContext);

        $order->setIntent($intent);
        $order->setPurchaseUnits(new PurchaseUnitCollection([$purchaseUnit]));
        $order->setApplicationContext($applicationContext);

        return $order;
    }

    private function createPurchaseUnit(
        SalesChannelContext $salesChannelContext,
        Cart $cart,
        ?CustomerEntity $customer
    ): PurchaseUnit {
        $cartTransaction = $cart->getTransactions()->first();
        if ($cartTransaction === null) {
            throw new InvalidTransactionException('');
        }

        $submitCart = $this->systemConfigService->getBool(Settings::SUBMIT_CART, $salesChannelContext->getSalesChannelId());

        $items = $submitCart ? $this->createItems($salesChannelContext->getCurrency(), $cart) : null;

        return $this->purchaseUnitProvider->createPurchaseUnit(
            $cartTransaction->getAmount(),
            $cart->getShippingCosts(),
            $customer,
            $items,
            $salesChannelContext,
            $cart->getPrice()->getTaxStatus() !== CartPrice::TAX_STATE_GROSS
        );
    }

    private function createItems(CurrencyEntity $currency, Cart $cart): ItemCollection
    {
        $items = new ItemCollection();
        $currencyCode = $currency->getIsoCode();

        foreach ($cart->getLineItems() as $lineItem) {
            $price = $lineItem->getPrice();

            if ($price === null) {
                continue;
            }

            $item = new Item();
            $this->setName($lineItem, $item);
            $this->setSku($lineItem, $item);

            $tax = new Money();
            $tax->setCurrencyCode($currencyCode);
            $tax->setValue($this->priceFormatter->formatPrice($price->getCalculatedTaxes()->getAmount(), $currencyCode));
            $item->setTax($tax);

            $unitAmount = new Money();
            $unitAmount->setCurrencyCode($currencyCode);
            $unitAmount->setValue($this->priceFormatter->formatPrice($price->getUnitPrice(), $currencyCode));

            $item->setUnitAmount($unitAmount);
            $item->setQuantity($lineItem->getQuantity());

            $event = new PayPalV2ItemFromCartEvent($item, $lineItem);
            $this->eventDispatcher->dispatch($event);

            $items->add($event->getPayPalLineItem());
        }

        return $items;
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
