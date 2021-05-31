<?php declare(strict_types=1);
/*
 * (c) shopware AG <info@shopware.com>
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Swag\PayPal\OrdersApi\Builder;

use Shopware\Core\Checkout\Cart\Price\Struct\CartPrice;
use Shopware\Core\Checkout\Customer\CustomerEntity;
use Shopware\Core\Checkout\Order\Aggregate\OrderTransaction\OrderTransactionEntity;
use Shopware\Core\Checkout\Order\OrderEntity;
use Shopware\Core\Checkout\Payment\Cart\AsyncPaymentTransactionStruct;
use Shopware\Core\System\SalesChannel\SalesChannelContext;
use Shopware\Core\System\SystemConfig\SystemConfigService;
use Swag\PayPal\OrdersApi\Builder\Util\AmountProvider;
use Swag\PayPal\OrdersApi\Builder\Util\ItemListProvider;
use Swag\PayPal\RestApi\V2\Api\Order;
use Swag\PayPal\RestApi\V2\Api\Order\ApplicationContext;
use Swag\PayPal\RestApi\V2\Api\Order\PurchaseUnit;
use Swag\PayPal\Setting\Service\SettingsServiceInterface;
use Swag\PayPal\Setting\Settings;
use Swag\PayPal\Util\PriceFormatter;

class OrderFromOrderBuilder extends AbstractOrderBuilder
{
    /**
     * @var ItemListProvider
     */
    private $itemListProvider;

    public function __construct(
        SettingsServiceInterface $settingsService,
        PriceFormatter $priceFormatter,
        AmountProvider $amountProvider,
        SystemConfigService $systemConfigService,
        ItemListProvider $itemListProvider
    ) {
        parent::__construct($settingsService, $priceFormatter, $amountProvider, $systemConfigService);
        $this->itemListProvider = $itemListProvider;
    }

    public function getOrder(
        AsyncPaymentTransactionStruct $paymentTransaction,
        SalesChannelContext $salesChannelContext,
        CustomerEntity $customer
    ): Order {
        $intent = $this->getIntent(null, $salesChannelContext->getSalesChannelId());
        $payer = $this->createPayer($customer);
        $purchaseUnit = $this->createPurchaseUnit(
            $salesChannelContext,
            $paymentTransaction->getOrder(),
            $paymentTransaction->getOrderTransaction(),
            $customer
        );
        $applicationContext = $this->createApplicationContext($salesChannelContext);
        $this->addReturnUrls($applicationContext, $paymentTransaction->getReturnUrl());

        $order = new Order();
        $order->setIntent($intent);
        $order->setPayer($payer);
        $order->setPurchaseUnits([$purchaseUnit]);
        $order->setApplicationContext($applicationContext);

        return $order;
    }

    private function createPurchaseUnit(
        SalesChannelContext $salesChannelContext,
        OrderEntity $order,
        OrderTransactionEntity $orderTransaction,
        CustomerEntity $customer
    ): PurchaseUnit {
        $currency = $salesChannelContext->getCurrency();
        $purchaseUnit = new PurchaseUnit();

        if ($this->systemConfigService === null) {
            // this can not occur, since this child's constructor is not nullable
            throw new \RuntimeException('No system settings available');
        }

        if ($this->systemConfigService->getBool(Settings::SUBMIT_CART, $salesChannelContext->getSalesChannelId())) {
            $items = $this->itemListProvider->getItemList($currency, $order);
            $purchaseUnit->setItems($items);
        }

        $amount = $this->amountProvider->createAmount(
            $orderTransaction->getAmount(),
            $order->getShippingCosts(),
            $currency,
            $purchaseUnit,
            $order->getTaxStatus() !== CartPrice::TAX_STATE_GROSS
        );
        $shipping = $this->createShipping($customer);

        $purchaseUnit->setAmount($amount);
        $purchaseUnit->setShipping($shipping);
        $purchaseUnit->setCustomId($orderTransaction->getId());
        $orderNumber = $order->getOrderNumber();
        if ($orderNumber !== null) {
            $purchaseUnit->setInvoiceId($orderNumber);
        }

        return $purchaseUnit;
    }

    private function addReturnUrls(ApplicationContext $applicationContext, string $returnUrl): void
    {
        $applicationContext->setReturnUrl($returnUrl);
        $applicationContext->setCancelUrl(\sprintf('%s&cancel=1', $returnUrl));
    }
}
