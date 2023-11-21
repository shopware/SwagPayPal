<?php declare(strict_types=1);
/*
 * (c) shopware AG <info@shopware.com>
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Swag\PayPal\OrdersApi\Builder;

use Shopware\Core\Checkout\Cart\Price\Struct\CartPrice;
use Shopware\Core\Checkout\Order\Aggregate\OrderTransaction\OrderTransactionEntity;
use Shopware\Core\Checkout\Order\OrderEntity;
use Shopware\Core\Checkout\Order\OrderException;
use Shopware\Core\Checkout\Payment\Cart\AsyncPaymentTransactionStruct;
use Shopware\Core\Checkout\Payment\Cart\SyncPaymentTransactionStruct;
use Shopware\Core\Framework\Log\Package;
use Shopware\Core\Framework\Validation\DataBag\RequestDataBag;
use Shopware\Core\System\SalesChannel\SalesChannelContext;
use Shopware\Core\System\SystemConfig\SystemConfigService;
use Swag\PayPal\Checkout\Exception\MissingPayloadException;
use Swag\PayPal\Checkout\Payment\Service\VaultTokenService;
use Swag\PayPal\OrdersApi\Builder\Util\AddressProvider;
use Swag\PayPal\OrdersApi\Builder\Util\ItemListProvider;
use Swag\PayPal\OrdersApi\Builder\Util\PurchaseUnitProvider;
use Swag\PayPal\RestApi\V2\Api\Common\Address;
use Swag\PayPal\RestApi\V2\Api\Common\Name;
use Swag\PayPal\RestApi\V2\Api\Order;
use Swag\PayPal\RestApi\V2\Api\Order\PaymentSource;
use Swag\PayPal\RestApi\V2\Api\Order\PaymentSource\Paypal;
use Swag\PayPal\RestApi\V2\Api\Order\PurchaseUnit;
use Swag\PayPal\RestApi\V2\Api\Order\PurchaseUnitCollection;
use Swag\PayPal\Setting\Settings;
use Swag\PayPal\Util\LocaleCodeProvider;

#[Package('checkout')]
class OrderFromOrderBuilder extends AbstractOrderBuilder
{
    /**
     * @internal
     */
    public function __construct(
        SystemConfigService $systemConfigService,
        PurchaseUnitProvider $purchaseUnitProvider,
        AddressProvider $addressProvider,
        LocaleCodeProvider $localeCodeProvider,
        private readonly ItemListProvider $itemListProvider,
        private readonly VaultTokenService $vaultTokenService,
    ) {
        parent::__construct($systemConfigService, $purchaseUnitProvider, $addressProvider, $localeCodeProvider);
    }

    public function getOrder(
        SyncPaymentTransactionStruct $paymentTransaction,
        RequestDataBag $requestDataBag,
        SalesChannelContext $salesChannelContext,
    ): Order {
        $intent = $this->getIntent($salesChannelContext->getSalesChannelId());
        $purchaseUnit = $this->createPurchaseUnit(
            $salesChannelContext,
            $paymentTransaction->getOrder(),
            $paymentTransaction->getOrderTransaction(),
        );

        $order = new Order();
        $order->setIntent($intent);
        $order->setPurchaseUnits(new PurchaseUnitCollection([$purchaseUnit]));
        $order->setPaymentSource($this->createPaymentSource($salesChannelContext, $requestDataBag, $paymentTransaction));

        return $order;
    }

    private function createPaymentSource(
        SalesChannelContext $salesChannelContext,
        RequestDataBag $requestDataBag,
        SyncPaymentTransactionStruct $paymentTransaction,
    ): PaymentSource {
        $paymentSource = new PaymentSource();
        $paypal = new Paypal();
        $paymentSource->setPaypal($paypal);

        $billingAddress = $paymentTransaction->getOrder()->getBillingAddress();
        if ($billingAddress === null) {
            throw OrderException::missingAssociation('billingAddress');
        }

        $address = new Address();
        $this->addressProvider->createAddress($billingAddress, $address);
        $paypal->setAddress($address);

        if ($token = $this->vaultTokenService->getAvailableToken($paymentTransaction, $salesChannelContext->getContext())) {
            $paypal->setVaultId($token->getToken());

            return $paymentSource;
        }

        $experienceContext = $this->createExperienceContext($salesChannelContext);
        $paypal->setExperienceContext($experienceContext);
        if ($paymentTransaction instanceof AsyncPaymentTransactionStruct) {
            $experienceContext->setReturnUrl($paymentTransaction->getReturnUrl());
            $experienceContext->setCancelUrl(\sprintf('%s&cancel=1', $paymentTransaction->getReturnUrl()));
        }

        $customer = $paymentTransaction->getOrder()->getOrderCustomer();
        if ($customer === null) {
            throw OrderException::missingAssociation('orderCustomer');
        }

        $paypal->setEmailAddress($customer->getEmail());
        $name = new Name();
        $name->setGivenName($customer->getFirstName());
        $name->setSurname($customer->getLastName());
        $paypal->setName($name);

        if ($this->vaultTokenService->getSubscription($paymentTransaction)) {
            $this->vaultTokenService->requestVaulting($paypal);
        }

        if ($requestDataBag->getBoolean(VaultTokenService::REQUEST_CREATE_VAULT)) {
            $this->vaultTokenService->requestVaulting($paypal);
        }

        return $paymentSource;
    }

    private function createPurchaseUnit(
        SalesChannelContext $salesChannelContext,
        OrderEntity $order,
        OrderTransactionEntity $orderTransaction
    ): PurchaseUnit {
        $submitCart = $this->systemConfigService->getBool(Settings::SUBMIT_CART, $salesChannelContext->getSalesChannelId());

        $items = $submitCart ? $this->itemListProvider->getItemList($order->getCurrency() ?? $salesChannelContext->getCurrency(), $order) : null;

        $purchaseUnit = $this->purchaseUnitProvider->createPurchaseUnit(
            $orderTransaction->getAmount(),
            $order->getShippingCosts(),
            null,
            $items,
            $salesChannelContext,
            $order->getTaxStatus() !== CartPrice::TAX_STATE_GROSS,
            $order,
            $orderTransaction
        );

        if (!$purchaseUnit->isset('shipping')) {
            throw new MissingPayloadException('created', 'purchaseUnit.shipping');
        }

        return $purchaseUnit;
    }
}
