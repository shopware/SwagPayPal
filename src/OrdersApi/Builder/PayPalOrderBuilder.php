<?php declare(strict_types=1);
/*
 * (c) shopware AG <info@shopware.com>
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Swag\PayPal\OrdersApi\Builder;

use Shopware\Core\Checkout\Cart\Cart;
use Shopware\Core\Checkout\Customer\Exception\AddressNotFoundException;
use Shopware\Core\Checkout\Order\Aggregate\OrderTransaction\OrderTransactionEntity;
use Shopware\Core\Checkout\Order\OrderEntity;
use Shopware\Core\Checkout\Order\OrderException;
use Shopware\Core\Checkout\Payment\Cart\PaymentTransactionStruct;
use Shopware\Core\Framework\Context;
use Shopware\Core\Framework\Log\Package;
use Shopware\Core\Framework\Validation\DataBag\RequestDataBag;
use Shopware\Core\System\SalesChannel\SalesChannelContext;
use Shopware\Core\System\SystemConfig\SystemConfigService;
use Shopware\PayPalSDK\Struct\V2\Common\Address;
use Shopware\PayPalSDK\Struct\V2\Common\Name;
use Shopware\PayPalSDK\Struct\V2\Order\PaymentSource;
use Shopware\PayPalSDK\Struct\V2\Order\PaymentSource\Common\AppSwitchContext;
use Shopware\PayPalSDK\Struct\V2\Order\PaymentSource\Common\AppSwitchContext\MobileWebContext;
use Shopware\PayPalSDK\Struct\V2\Order\PaymentSource\Common\ExperienceContext;
use Shopware\PayPalSDK\Struct\V2\Order\PaymentSource\Paypal;
use Swag\PayPal\Checkout\Payment\Service\VaultTokenService;
use Swag\PayPal\Checkout\SalesChannel\AbstractCreateOrderRoute;
use Swag\PayPal\OrdersApi\Builder\Util\AddressProvider;
use Swag\PayPal\OrdersApi\Builder\Util\ItemListProvider;
use Swag\PayPal\OrdersApi\Builder\Util\PurchaseUnitProvider;
use Swag\PayPal\Setting\Settings;
use Swag\PayPal\Util\LocaleCodeProvider;
use Symfony\Component\HttpFoundation\Request;

#[Package('checkout')]
class PayPalOrderBuilder extends AbstractOrderBuilder
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
        $paypal = new Paypal();
        $paymentSource->setPaypal($paypal);

        $billingAddress = $order->getBillingAddress();
        if ($billingAddress === null) {
            throw OrderException::missingAssociation('billingAddress');
        }

        $address = new Address();
        $this->addressProvider->createAddress($billingAddress, $address);
        $paypal->setAddress($address);

        if (!$request->attributes->getBoolean(self::PRELIMINARY_ATTRIBUTE) && $token = $this->vaultTokenService->getAvailableToken($paymentTransaction, $orderTransaction, $order, $context)) {
            $paypal->setVaultId($token->getToken());

            return;
        }

        $salesChannel = $order->getSalesChannel();
        \assert($salesChannel !== null);
        /** @phpstan-ignore method.deprecated */
        $experienceContext = $this->createExperienceContext($order, $salesChannel, $context, $paymentTransaction, new RequestDataBag($request->request->all()));
        $requestVaulting = $this->vaultTokenService->shouldRequestVaulting(bag: $request->request, paymentTransaction: $paymentTransaction);
        $this->configureAppSwitchContext($experienceContext, $order->getSalesChannelId(), $request, $requestVaulting);
        $paypal->setExperienceContext($experienceContext);

        $customer = $order->getOrderCustomer();
        if ($customer === null) {
            throw OrderException::missingAssociation('orderCustomer');
        }

        $paypal->setEmailAddress($customer->getEmail());
        $name = new Name();
        $name->setGivenName($customer->getFirstName());
        $name->setSurname($customer->getLastName());
        $paypal->setName($name);

        if ($requestVaulting) {
            $this->vaultTokenService->requestVaulting($paypal);
        }
    }

    protected function buildPaymentSourceFromCart(Cart $cart, SalesChannelContext $salesChannelContext, RequestDataBag $requestDataBag, PaymentSource $paymentSource): void
    {
        $paypal = new Paypal();
        $paymentSource->setPaypal($paypal);

        /** @phpstan-ignore method.deprecated */
        $experienceContext = $this->createExperienceContext($cart, $salesChannelContext->getSalesChannel(), $salesChannelContext->getContext(), null, $requestDataBag);
        $requestVaulting = $this->vaultTokenService->shouldRequestVaulting($salesChannelContext, $requestDataBag);
        $this->configureAppSwitchContext($experienceContext, $salesChannelContext->getSalesChannelId(), $requestDataBag, $requestVaulting);
        $paypal->setExperienceContext($experienceContext);

        $customer = $salesChannelContext->getCustomer();
        if ($customer === null) {
            return;
        }

        $paypal->setEmailAddress($customer->getEmail());
        $name = new Name();
        $name->setGivenName($customer->getFirstName());
        $name->setSurname($customer->getLastName());
        $paypal->setName($name);

        $billingAddress = $customer->getActiveBillingAddress();
        if ($billingAddress === null) {
            throw new AddressNotFoundException($customer->getDefaultBillingAddressId());
        }
        $address = new Address();
        $this->addressProvider->createAddress($billingAddress, $address);
        $paypal->setAddress($address);

        if ($requestVaulting) {
            $this->vaultTokenService->requestVaulting($paypal);
        }
    }

    private function configureAppSwitchContext(ExperienceContext $experienceContext, string $salesChannelId, Request|RequestDataBag $request, bool $requestVaulting): void
    {
        if ($requestVaulting || !$this->systemConfigService->getBool(Settings::SPB_APP_SWITCH_ENABLED, $salesChannelId)) {
            return;
        }

        if ($this->getRequestString($request, 'product') !== 'spb') {
            return;
        }

        $buyerUserAgent = $this->getRequestString($request, AbstractCreateOrderRoute::PAYPAL_BUYER_USER_AGENT);
        if ($buyerUserAgent === '') {
            return;
        }

        $mobileWebContext = new MobileWebContext();
        $mobileWebContext->setReturnFlow(MobileWebContext::RETURN_FLOW_AUTO);
        $mobileWebContext->setBuyerUserAgent(\substr($buyerUserAgent, 0, 512));

        $appSwitchContext = new AppSwitchContext();
        $appSwitchContext->setMobileWeb($mobileWebContext);

        $experienceContext->setAppSwitchContext($appSwitchContext);
    }

    private function getRequestString(Request|RequestDataBag $request, string $key): string
    {
        if ($request instanceof Request && $key === AbstractCreateOrderRoute::PAYPAL_BUYER_USER_AGENT) {
            return $request->headers->get('user-agent') ?? '';
        }

        $value = $request instanceof Request ? $request->request->get($key) : $request->get($key);

        return \is_string($value) ? $value : '';
    }
}
