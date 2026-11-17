<?php declare(strict_types=1);
/*
 * (c) shopware AG <info@shopware.com>
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Swag\PayPal\OrdersApi\Builder;

use Shopware\Core\Checkout\Cart\Cart;
use Shopware\Core\Checkout\Payment\Cart\RecurringPaymentTransactionStruct;
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
use Swag\PayPal\Setting\Settings;
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

        // only renewals are built from a recurring struct (see ACDCHandler::captureRecurring()), the checkout always has the payer present
        $isRecurring = $paymentTransaction instanceof RecurringPaymentTransactionStruct;

        // a renewal has no payer to complete a 3D Secure challenge, so requesting one leaves it at PAYER_ACTION_REQUIRED
        if (!$isRecurring) {
            $card->setAttributes($this->createVerificationAttributes($salesChannelContext->getSalesChannelId()));
        }

        $paymentSource->setCard($card);

        if (!$requestDataBag->getBoolean(self::PRELIMINARY_ATTRIBUTE) && $token = $this->vaultTokenService->getAvailableToken($paymentTransaction, $salesChannelContext->getContext())) {
            $card->setVaultId($token->getToken());
            $card->setStoredCredential($this->createStoredCredential(
                $isRecurring ? StoredCredential::PAYMENT_INITIATOR_MERCHANT : StoredCredential::PAYMENT_INITIATOR_CUSTOMER,
                $this->vaultTokenService->getSubscription($paymentTransaction) ? StoredCredential::PAYMENT_TYPE_RECURRING : StoredCredential::PAYMENT_TYPE_UNSCHEDULED,
                StoredCredential::USAGE_SUBSEQUENT,
            ));

            return;
        }

        if ($this->vaultTokenService->getSubscription($paymentTransaction)) {
            $this->vaultTokenService->requestVaulting($card);
            $card->setStoredCredential($this->createStoredCredential(StoredCredential::PAYMENT_INITIATOR_CUSTOMER, StoredCredential::PAYMENT_TYPE_RECURRING, StoredCredential::USAGE_FIRST));
        }

        if ($requestDataBag->getBoolean(VaultTokenService::REQUEST_CREATE_VAULT)) {
            $this->vaultTokenService->requestVaulting($card);
        }
    }

    protected function buildPaymentSourceFromCart(Cart $cart, SalesChannelContext $salesChannelContext, RequestDataBag $requestDataBag, PaymentSource $paymentSource): void
    {
        $card = new Card();
        $card->setExperienceContext($this->createExperienceContext($salesChannelContext, $cart));
        $card->setAttributes($this->createVerificationAttributes($salesChannelContext->getSalesChannelId()));

        $paymentSource->setCard($card);

        if ($salesChannelContext->hasExtension('subscription')) {
            $this->vaultTokenService->requestVaulting($card);
            $card->setStoredCredential($this->createStoredCredential(StoredCredential::PAYMENT_INITIATOR_CUSTOMER, StoredCredential::PAYMENT_TYPE_RECURRING, StoredCredential::USAGE_FIRST));
        }

        if ($requestDataBag->getBoolean(VaultTokenService::REQUEST_CREATE_VAULT)) {
            $this->vaultTokenService->requestVaulting($card);
        }
    }

    private function createVerificationAttributes(string $salesChannelId): Attributes
    {
        $attributes = new Attributes();
        $verification = new Verification();
        if (!$this->systemConfigService->getBool(Settings::ACDC_FORCE_3DS, $salesChannelId)) {
            $verification->setMethod(Verification::METHOD_SCA_WHEN_REQUIRED);
        }
        $attributes->setVerification($verification);

        return $attributes;
    }

    /**
     * The card-on-file indicators PayPal passes to the issuer. A subscription's first payment opens a recurring
     * series, which is what exempts its merchant-initiated renewals from SCA.
     *
     * @see https://developer.paypal.com/docs/checkout/advanced/customize/sca-payment-indicators
     */
    private function createStoredCredential(string $paymentInitiator, string $paymentType, string $usage): StoredCredential
    {
        $storedCredential = new StoredCredential();
        $storedCredential->setPaymentInitiator($paymentInitiator);
        $storedCredential->setPaymentType($paymentType);
        $storedCredential->setUsage($usage);

        return $storedCredential;
    }
}
