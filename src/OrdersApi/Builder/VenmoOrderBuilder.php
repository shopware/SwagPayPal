<?php declare(strict_types=1);
/*
 * (c) shopware AG <info@shopware.com>
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Swag\PayPal\OrdersApi\Builder;

use Shopware\Core\Checkout\Cart\Cart;
use Shopware\Core\Checkout\Order\OrderException;
use Shopware\Core\Checkout\Payment\Cart\SyncPaymentTransactionStruct;
use Shopware\Core\Framework\Log\Package;
use Shopware\Core\Framework\Validation\DataBag\RequestDataBag;
use Shopware\Core\System\SalesChannel\SalesChannelContext;
use Shopware\Core\System\SystemConfig\SystemConfigService;
use Swag\PayPal\Checkout\Payment\Service\VaultTokenService;
use Swag\PayPal\OrdersApi\Builder\Util\AddressProvider;
use Swag\PayPal\OrdersApi\Builder\Util\ItemListProvider;
use Swag\PayPal\OrdersApi\Builder\Util\PurchaseUnitProvider;
use Swag\PayPal\RestApi\V2\Api\Order\PaymentSource;
use Swag\PayPal\RestApi\V2\Api\Order\PaymentSource\Venmo;
use Swag\PayPal\Util\LocaleCodeProvider;

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
        SyncPaymentTransactionStruct $paymentTransaction,
        SalesChannelContext $salesChannelContext,
        RequestDataBag $requestDataBag,
        PaymentSource $paymentSource,
    ): void {
        $venmo = new Venmo();
        $paymentSource->setVenmo($venmo);

        $experienceContext = $this->createExperienceContext($salesChannelContext, $paymentTransaction);
        $venmo->setExperienceContext($experienceContext);

        if ($token = $this->vaultTokenService->getAvailableToken($paymentTransaction, $salesChannelContext->getContext())) {
            $venmo->setVaultId($token->getToken());
        } else {
            $customer = $paymentTransaction->getOrder()->getOrderCustomer();
            if ($customer === null) {
                throw OrderException::missingAssociation('orderCustomer');
            }

            $venmo->setEmailAddress($customer->getEmail());
        }

        if ($this->vaultTokenService->getSubscription($paymentTransaction)) {
            $this->vaultTokenService->requestVaulting($venmo);
        }

        if ($requestDataBag->getBoolean(VaultTokenService::REQUEST_CREATE_VAULT)) {
            $this->vaultTokenService->requestVaulting($venmo);
        }
    }

    protected function buildPaymentSourceFromCart(Cart $cart, SalesChannelContext $salesChannelContext, RequestDataBag $requestDataBag, PaymentSource $paymentSource): void
    {
        $venmo = new Venmo();
        $paymentSource->setVenmo($venmo);

        $venmo->setExperienceContext($this->createExperienceContext($salesChannelContext));

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
