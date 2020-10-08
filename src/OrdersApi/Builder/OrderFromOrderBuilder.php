<?php declare(strict_types=1);
/*
 * (c) shopware AG <info@shopware.com>
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Swag\PayPal\OrdersApi\Builder;

use Shopware\Core\Checkout\Customer\CustomerEntity;
use Shopware\Core\Checkout\Order\Aggregate\OrderTransaction\OrderTransactionEntity;
use Shopware\Core\Checkout\Order\OrderEntity;
use Shopware\Core\Checkout\Payment\Cart\AsyncPaymentTransactionStruct;
use Shopware\Core\System\SalesChannel\SalesChannelContext;
use Swag\PayPal\OrdersApi\Builder\Util\AmountProvider;
use Swag\PayPal\OrdersApi\Builder\Util\ItemListProvider;
use Swag\PayPal\RestApi\V2\Api\Order;
use Swag\PayPal\RestApi\V2\Api\Order\ApplicationContext;
use Swag\PayPal\RestApi\V2\Api\Order\PurchaseUnit;
use Swag\PayPal\Setting\Service\SettingsServiceInterface;
use Swag\PayPal\Setting\SwagPayPalSettingStruct;
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
        ItemListProvider $itemListProvider
    ) {
        parent::__construct($settingsService, $priceFormatter, $amountProvider);
        $this->itemListProvider = $itemListProvider;
    }

    public function getOrder(
        AsyncPaymentTransactionStruct $paymentTransaction,
        SalesChannelContext $salesChannelContext,
        CustomerEntity $customer
    ): Order {
        $settings = $this->settingsService->getSettings($salesChannelContext->getSalesChannel()->getId());

        $intent = $this->getIntent();
        $payer = $this->createPayer($customer);
        $purchaseUnit = $this->createPurchaseUnit(
            $salesChannelContext,
            $paymentTransaction->getOrder(),
            $paymentTransaction->getOrderTransaction(),
            $customer,
            $settings
        );
        $applicationContext = $this->createApplicationContext($salesChannelContext, $settings);
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
        CustomerEntity $customer,
        SwagPayPalSettingStruct $settings
    ): PurchaseUnit {
        $currency = $salesChannelContext->getCurrency();
        $purchaseUnit = new PurchaseUnit();

        if ($settings->getSubmitCart()) {
            $items = $this->itemListProvider->getItemList($currency, $order);
            $purchaseUnit->setItems($items);
        }

        $amount = $this->amountProvider->createAmount(
            $orderTransaction->getAmount(),
            $order->getShippingCosts(),
            $currency,
            $purchaseUnit
        );
        $shipping = $this->createShipping($customer);

        $purchaseUnit->setAmount($amount);
        $purchaseUnit->setShipping($shipping);
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
