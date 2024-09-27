<?php declare(strict_types=1);
/*
 * (c) shopware AG <info@shopware.com>
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Swag\PayPal\OrdersApi\Builder;

use Shopware\Core\Checkout\Cart\Cart;
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
use Swag\PayPal\RestApi\V2\Api\Order\PaymentSource\Card;
use Swag\PayPal\RestApi\V2\Api\Order\PaymentSource\Card\StoredCredential;
use Swag\PayPal\RestApi\V2\Api\Order\PaymentSource\Common\Attributes;
use Swag\PayPal\RestApi\V2\Api\Order\PaymentSource\Common\Attributes\Verification;
use Swag\PayPal\Util\LocaleCodeProvider;

#[Package('checkout')]
class ACDCOrderBuilder extends AbstractOrderBuilder
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
        $card = new Card();
        $card->setExperienceContext($this->createExperienceContext($salesChannelContext, $paymentTransaction));

        $attributes = new Attributes();
        $attributes->setVerification(new Verification());
        $card->setAttributes($attributes);

        $paymentSource->setCard($card);

        if ($token = $this->vaultTokenService->getAvailableToken($paymentTransaction, $salesChannelContext->getContext())) {
            $card->setVaultId($token->getToken());
            $storedCredential = new StoredCredential();

            if ($this->vaultTokenService->getSubscription($paymentTransaction)) {
                $storedCredential->setPaymentInitiator(StoredCredential::PAYMENT_INITIATOR_MERCHANT);
                $storedCredential->setPaymentType(StoredCredential::PAYMENT_TYPE_RECURRING);
                $storedCredential->setUsage(StoredCredential::USAGE_DERIVED);
            } else {
                $storedCredential->setPaymentInitiator(StoredCredential::PAYMENT_INITIATOR_CUSTOMER);
                $storedCredential->setPaymentType(StoredCredential::PAYMENT_TYPE_UNSCHEDULED);
                $storedCredential->setUsage(StoredCredential::USAGE_SUBSEQUENT);
            }

            $card->setStoredCredential($storedCredential);

            return;
        }

        if ($this->vaultTokenService->getSubscription($paymentTransaction)) {
            $this->vaultTokenService->requestVaulting($card);
        }

        if ($requestDataBag->getBoolean(VaultTokenService::REQUEST_CREATE_VAULT)) {
            $this->vaultTokenService->requestVaulting($card);
        }
    }

    protected function buildPaymentSourceFromCart(Cart $cart, SalesChannelContext $salesChannelContext, RequestDataBag $requestDataBag, PaymentSource $paymentSource): void
    {
        $card = new Card();
        $card->setExperienceContext($this->createExperienceContext($salesChannelContext, $cart));

        $attributes = new Attributes();
        $attributes->setVerification(new Verification());
        $card->setAttributes($attributes);

        $paymentSource->setCard($card);

        if ($salesChannelContext->hasExtension('subscription')) {
            $this->vaultTokenService->requestVaulting($card);
        }

        if ($requestDataBag->getBoolean(VaultTokenService::REQUEST_CREATE_VAULT)) {
            $this->vaultTokenService->requestVaulting($card);
        }
    }
}
