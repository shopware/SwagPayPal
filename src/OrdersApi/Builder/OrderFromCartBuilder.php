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
use Shopware\Core\System\Currency\CurrencyEntity;
use Shopware\Core\System\SalesChannel\SalesChannelContext;
use Shopware\Core\System\SystemConfig\SystemConfigService;
use Swag\PayPal\OrdersApi\Builder\Event\PayPalV2ItemFromCartEvent;
use Swag\PayPal\OrdersApi\Builder\Util\AmountProvider;
use Swag\PayPal\OrdersApi\Builder\Util\PurchaseUnitProvider;
use Swag\PayPal\RestApi\V2\Api\Order;
use Swag\PayPal\RestApi\V2\Api\Order\PurchaseUnit;
use Swag\PayPal\RestApi\V2\Api\Order\PurchaseUnit\Item;
use Swag\PayPal\RestApi\V2\Api\Order\PurchaseUnit\Item\UnitAmount;
use Swag\PayPal\Setting\Service\SettingsServiceInterface;
use Swag\PayPal\Setting\Settings;
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
        SystemConfigService $systemConfigService,
        PurchaseUnitProvider $purchaseUnitProvider,
        EventDispatcherInterface $eventDispatcher,
        LoggerInterface $logger
    ) {
        parent::__construct($settingsService, $priceFormatter, $amountProvider, $systemConfigService, $purchaseUnitProvider);
        $this->eventDispatcher = $eventDispatcher;
        $this->logger = $logger;
    }

    public function getOrder(
        Cart $cart,
        SalesChannelContext $salesChannelContext,
        ?CustomerEntity $customer,
        bool $isExpressCheckout = false
    ): Order {
        $order = new Order();

        $intent = $this->getIntent(null, $salesChannelContext->getSalesChannelId());
        if ($customer !== null) {
            $payer = $this->createPayer($customer);
            $order->setPayer($payer);
        }
        $purchaseUnit = $this->createPurchaseUnit($salesChannelContext, $cart, $customer, $isExpressCheckout);
        $applicationContext = $this->createApplicationContext($salesChannelContext);

        $order->setIntent($intent);
        $order->setPurchaseUnits([$purchaseUnit]);
        $order->setApplicationContext($applicationContext);

        return $order;
    }

    private function createPurchaseUnit(
        SalesChannelContext $salesChannelContext,
        Cart $cart,
        ?CustomerEntity $customer,
        bool $isExpressCheckoutProcess
    ): PurchaseUnit {
        $cartTransaction = $cart->getTransactions()->first();
        if ($cartTransaction === null) {
            throw new InvalidTransactionException('');
        }

        if ($this->systemConfigService === null) {
            // this can not occur, since this child's constructor is not nullable
            throw new \RuntimeException('No system settings available');
        }

        if ($this->purchaseUnitProvider === null) {
            // this can not occur, since this child's constructor is not nullable
            throw new \RuntimeException('No purchase unit provider available');
        }

        $submitCart = ($isExpressCheckoutProcess && $this->systemConfigService->getBool(Settings::ECS_SUBMIT_CART, $salesChannelContext->getSalesChannelId()))
            || (!$isExpressCheckoutProcess && $this->systemConfigService->getBool(Settings::SUBMIT_CART, $salesChannelContext->getSalesChannelId()));

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
            $this->setName($lineItem, $item);
            $this->setSku($lineItem, $item);

            $unitAmount = new UnitAmount();
            $unitAmount->setCurrencyCode($currencyCode);
            $unitAmount->setValue($this->priceFormatter->formatPrice($price->getUnitPrice()));

            $item->setUnitAmount($unitAmount);
            $item->setQuantity($lineItem->getQuantity());

            $event = new PayPalV2ItemFromCartEvent($item, $lineItem);
            $this->eventDispatcher->dispatch($event);

            $items[] = $event->getPayPalLineItem();
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
