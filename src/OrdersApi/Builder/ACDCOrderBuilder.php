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
        $card->setExperienceContext($this->createExperienceContext($order, $salesChannel, $context, $paymentTransaction));

        $attributes = new Attributes();
        $attributes->setVerification(new Verification());
        $card->setAttributes($attributes);

        $paymentSource->setCard($card);

        if (!$request->attributes->getBoolean(self::PRELIMINARY_ATTRIBUTE) && $token = $this->vaultTokenService->getAvailableToken($paymentTransaction, $orderTransaction, $order, $context)) {
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

        if ($request->request->getBoolean(VaultTokenService::REQUEST_CREATE_VAULT)) {
            $this->vaultTokenService->requestVaulting($card);
        }
    }

    protected function buildPaymentSourceFromCart(Cart $cart, SalesChannelContext $salesChannelContext, RequestDataBag $requestDataBag, PaymentSource $paymentSource): void
    {
        $card = new Card();
        $card->setExperienceContext($this->createExperienceContext($cart, $salesChannelContext->getSalesChannel(), $salesChannelContext->getContext()));

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
