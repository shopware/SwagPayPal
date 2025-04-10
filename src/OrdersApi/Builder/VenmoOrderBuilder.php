<?php declare(strict_types=1);
/*
 * (c) shopware AG <info@shopware.com>
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Swag\PayPal\OrdersApi\Builder;

use Shopware\Core\Checkout\Cart\Cart;
use Shopware\Core\Checkout\Order\Aggregate\OrderTransaction\OrderTransactionEntity;
use Shopware\Core\Checkout\Order\OrderEntity;
use Shopware\Core\Checkout\Order\OrderException;
use Shopware\Core\Checkout\Payment\Cart\PaymentTransactionStruct;
use Shopware\Core\Framework\Context;
use Shopware\Core\Framework\Log\Package;
use Shopware\Core\Framework\Validation\DataBag\RequestDataBag;
use Shopware\Core\System\SalesChannel\SalesChannelContext;
use Shopware\Core\System\SystemConfig\SystemConfigService;
use Shopware\PayPalSDK\Struct\V2\Order\PaymentSource;
use Shopware\PayPalSDK\Struct\V2\Order\PaymentSource\Venmo;
use Swag\PayPal\Checkout\Payment\Service\VaultTokenService;
use Swag\PayPal\OrdersApi\Builder\Util\AddressProvider;
use Swag\PayPal\OrdersApi\Builder\Util\ItemListProvider;
use Swag\PayPal\OrdersApi\Builder\Util\PurchaseUnitProvider;
use Swag\PayPal\Util\LocaleCodeProvider;
use Symfony\Component\HttpFoundation\Request;

#[Package('checkout')]
class VenmoOrderBuilder extends AbstractOrderBuilder
{
    /**
     * @internal
     */
    public function __construct(
        SystemConfigService $systemConfigService,
        PurchaseUnitProvider $purchaseUnitProvider,
        AddressProvider $addressProvider,
        LocaleCodeProvider $localeCodeProvider,
        ItemListProvider $itemListProvider,
        private readonly VaultTokenService $vaultTokenService,
    ) {
        parent::__construct($systemConfigService, $purchaseUnitProvider, $addressProvider, $localeCodeProvider, $itemListProvider);
    }

    protected function buildPaymentSource(
        PaymentTransactionStruct $paymentTransaction,
        OrderTransactionEntity $orderTransaction,
        OrderEntity $order,
        Context $context,
        Request $request,
        PaymentSource $paymentSource,
        bool $isPreliminary = false,
    ): void {
        $venmo = new Venmo();
        $paymentSource->setVenmo($venmo);

        $salesChannel = $order->getSalesChannel();
        \assert($salesChannel !== null);
        $experienceContext = $this->createExperienceContext($order, $salesChannel, $context, $paymentTransaction);
        $venmo->setExperienceContext($experienceContext);

        if (!$request->attributes->getBoolean(self::PRELIMINARY_ATTRIBUTE) && $token = $this->vaultTokenService->getAvailableToken($paymentTransaction, $orderTransaction, $order, $context)) {
            $venmo->setVaultId($token->getToken());
        } else {
            $customer = $order->getOrderCustomer();
            if ($customer === null) {
                throw OrderException::missingAssociation('orderCustomer');
            }

            $venmo->setEmailAddress($customer->getEmail());
        }

        if ($this->vaultTokenService->getSubscription($paymentTransaction)) {
            $this->vaultTokenService->requestVaulting($venmo);
        }

        if ($request->request->getBoolean(VaultTokenService::REQUEST_CREATE_VAULT)) {
            $this->vaultTokenService->requestVaulting($venmo);
        }
    }

    protected function buildPaymentSourceFromCart(Cart $cart, SalesChannelContext $salesChannelContext, RequestDataBag $requestDataBag, PaymentSource $paymentSource): void
    {
        $venmo = new Venmo();
        $paymentSource->setVenmo($venmo);

        $venmo->setExperienceContext($this->createExperienceContext($cart, $salesChannelContext->getSalesChannel(), $salesChannelContext->getContext()));

        $customer = $salesChannelContext->getCustomer();
        if ($customer === null) {
            return;
        }

        $venmo->setEmailAddress($customer->getEmail());

        if ($salesChannelContext->hasExtension('subscription')) {
            $this->vaultTokenService->requestVaulting($venmo);
        }

        if ($requestDataBag->getBoolean(VaultTokenService::REQUEST_CREATE_VAULT)) {
            $this->vaultTokenService->requestVaulting($venmo);
        }
    }
}
