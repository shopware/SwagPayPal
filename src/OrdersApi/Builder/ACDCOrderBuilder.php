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
use Shopware\Core\Checkout\Payment\Cart\PaymentTransactionStruct;
use Shopware\Core\Framework\Context;
use Shopware\Core\Framework\Log\Package;
use Shopware\Core\Framework\Validation\DataBag\RequestDataBag;
use Shopware\Core\System\SalesChannel\SalesChannelContext;
use Shopware\Core\System\SystemConfig\SystemConfigService;
use Shopware\PayPalSDK\Struct\V2\Order\PaymentSource;
use Shopware\PayPalSDK\Struct\V2\Order\PaymentSource\Card;
use Shopware\PayPalSDK\Struct\V2\Order\PaymentSource\Card\StoredCredential;
use Shopware\PayPalSDK\Struct\V2\Order\PaymentSource\Common\Attributes;
use Shopware\PayPalSDK\Struct\V2\Order\PaymentSource\Common\Attributes\Verification;
use Swag\PayPal\Checkout\Payment\Service\VaultTokenService;
use Swag\PayPal\OrdersApi\Builder\Util\AddressProvider;
use Swag\PayPal\OrdersApi\Builder\Util\ItemListProvider;
use Swag\PayPal\OrdersApi\Builder\Util\PurchaseUnitProvider;
use Swag\PayPal\Setting\Settings;
use Swag\PayPal\Util\LocaleCodeProvider;
use Symfony\Component\HttpFoundation\Request;

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
        PaymentTransactionStruct $paymentTransaction,
        OrderTransactionEntity $orderTransaction,
        OrderEntity $order,
        Context $context,
        Request $request,
        PaymentSource $paymentSource,
    ): void {
        $salesChannel = $order->getSalesChannel();
        \assert($salesChannel !== null);
        $card = new Card();
        /** @phpstan-ignore method.deprecated */
        $card->setExperienceContext($this->createExperienceContext($order, $salesChannel, $context, $paymentTransaction, new RequestDataBag($request->request->all())));

        $isRecurring = $this->isRecurringOrder($request);

        // a renewal has no payer to complete a 3D Secure challenge, so requesting one leaves it at PAYER_ACTION_REQUIRED
        if (!$isRecurring) {
            $card->setAttributes($this->createVerificationAttributes($order->getSalesChannelId()));
        }

        $paymentSource->setCard($card);

        if (!$request->attributes->getBoolean(self::PRELIMINARY_ATTRIBUTE) && $token = $this->vaultTokenService->getAvailableToken($paymentTransaction, $orderTransaction, $order, $context)) {
            $card->setVaultId($token->getToken());
            $card->setStoredCredential($this->createStoredCredential(
                $isRecurring ? StoredCredential::PAYMENT_INITIATOR_MERCHANT : StoredCredential::PAYMENT_INITIATOR_CUSTOMER,
                $this->vaultTokenService->getSubscriptions($paymentTransaction) ? StoredCredential::PAYMENT_TYPE_RECURRING : StoredCredential::PAYMENT_TYPE_UNSCHEDULED,
                StoredCredential::USAGE_SUBSEQUENT,
            ));

            return;
        }

        if ($this->vaultTokenService->shouldRequestVaulting(bag: $request->request, paymentTransaction: $paymentTransaction)) {
            $this->vaultTokenService->requestVaulting($card);

            if ($this->vaultTokenService->getSubscriptions($paymentTransaction)) {
                $card->setStoredCredential($this->createStoredCredential(StoredCredential::PAYMENT_INITIATOR_CUSTOMER, StoredCredential::PAYMENT_TYPE_RECURRING, StoredCredential::USAGE_FIRST));
            }
        }
    }

    protected function buildPaymentSourceFromCart(Cart $cart, SalesChannelContext $salesChannelContext, RequestDataBag $requestDataBag, PaymentSource $paymentSource): void
    {
        $card = new Card();
        /** @phpstan-ignore method.deprecated */
        $card->setExperienceContext($this->createExperienceContext($cart, $salesChannelContext->getSalesChannel(), $salesChannelContext->getContext(), null, $requestDataBag));

        $card->setAttributes($this->createVerificationAttributes($salesChannelContext->getSalesChannelId()));

        $paymentSource->setCard($card);

        if ($this->vaultTokenService->shouldRequestVaulting($salesChannelContext, $requestDataBag)) {
            $this->vaultTokenService->requestVaulting($card);

            if ($this->vaultTokenService->isSubscriptionContext($salesChannelContext)) {
                $card->setStoredCredential($this->createStoredCredential(StoredCredential::PAYMENT_INITIATOR_CUSTOMER, StoredCredential::PAYMENT_TYPE_RECURRING, StoredCredential::USAGE_FIRST));
            }
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
