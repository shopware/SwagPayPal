<?php declare(strict_types=1);
/*
 * (c) shopware AG <info@shopware.com>
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Swag\PayPal\Checkout\ACDC\Service;

use Shopware\Core\Checkout\Cart\Exception\CustomerNotLoggedInException;
use Shopware\Core\Checkout\Customer\Aggregate\CustomerAddress\CustomerAddressEntity;
use Shopware\Core\Checkout\Customer\Exception\AddressNotFoundException;
use Shopware\Core\Checkout\Order\Aggregate\OrderAddress\OrderAddressEntity;
use Shopware\Core\Checkout\Order\OrderEntity;
use Shopware\Core\Framework\DataAbstractionLayer\Entity;
use Shopware\Core\System\SalesChannel\SalesChannelContext;
use Shopware\Core\System\SystemConfig\SystemConfigService;
use Swag\PayPal\Checkout\ACDC\ACDCCheckoutFieldData;
use Swag\PayPal\Checkout\ACDC\Struct\BillingAddress;
use Swag\PayPal\Checkout\ACDC\Struct\CardholderData;
use Swag\PayPal\RestApi\V1\Resource\IdentityResource;
use Swag\PayPal\Setting\Settings;
use Swag\PayPal\Util\Lifecycle\Method\ACDCMethodData;
use Swag\PayPal\Util\Lifecycle\Method\PaymentMethodDataRegistry;
use Swag\PayPal\Util\LocaleCodeProvider;
use Symfony\Component\Routing\RouterInterface;

class ACDCCheckoutDataService implements ACDCCheckoutDataServiceInterface
{
    private PaymentMethodDataRegistry $paymentMethodDataRegistry;

    private IdentityResource $identityResource;

    private LocaleCodeProvider $localeCodeProvider;

    private RouterInterface $router;

    private SystemConfigService $systemConfigService;

    public function __construct(
        PaymentMethodDataRegistry $paymentMethodDataRegistry,
        IdentityResource $identityResource,
        LocaleCodeProvider $localeCodeProvider,
        RouterInterface $router,
        SystemConfigService $systemConfigService
    ) {
        $this->paymentMethodDataRegistry = $paymentMethodDataRegistry;
        $this->identityResource = $identityResource;
        $this->localeCodeProvider = $localeCodeProvider;
        $this->router = $router;
        $this->systemConfigService = $systemConfigService;
    }

    public function buildCheckoutData(
        SalesChannelContext $context,
        ?OrderEntity $order = null
    ): ACDCCheckoutFieldData {
        $paymentMethodId = $this->paymentMethodDataRegistry->getEntityIdFromData(
            $this->paymentMethodDataRegistry->getPaymentMethod(ACDCMethodData::class),
            $context->getContext()
        );

        $salesChannelId = $context->getSalesChannelId();
        $clientId = $this->systemConfigService->getBool(Settings::SANDBOX, $salesChannelId)
            ? $this->systemConfigService->getString(Settings::CLIENT_ID_SANDBOX, $salesChannelId)
            : $this->systemConfigService->getString(Settings::CLIENT_ID, $salesChannelId);
        $customer = $context->getCustomer();

        if ($customer === null) {
            throw new CustomerNotLoggedInException();
        }

        $acdcCheckoutFieldData = (new ACDCCheckoutFieldData())->assign([
            'clientId' => $clientId,
            'clientToken' => $this->identityResource->getClientToken($salesChannelId)->getClientToken(),
            'languageIso' => $this->getButtonLanguage($context),
            'currency' => $context->getCurrency()->getIsoCode(),
            'intent' => \mb_strtolower($this->systemConfigService->getString(Settings::INTENT, $salesChannelId)),
            'buttonShape' => $this->systemConfigService->getString(Settings::SPB_BUTTON_SHAPE, $salesChannelId),
            'paymentMethodId' => $paymentMethodId,
            'createOrderUrl' => $this->router->generate('store-api.paypal.create_order'),
            'checkoutConfirmUrl' => $this->router->generate('frontend.checkout.confirm.page', [], RouterInterface::ABSOLUTE_URL),
            'addErrorUrl' => $this->router->generate('store-api.paypal.error'),
            'cardholderData' => $this->getCardholderData($order ? $order->getBillingAddress() : $customer->getActiveBillingAddress()),
        ]);

        if ($order !== null) {
            $acdcCheckoutFieldData->setOrderId($order->getId());
            $acdcCheckoutFieldData->setAccountOrderEditUrl(
                $this->router->generate(
                    'frontend.account.edit-order.page',
                    ['orderId' => $order->getId()],
                    RouterInterface::ABSOLUTE_URL
                )
            );
        }

        return $acdcCheckoutFieldData;
    }

    /**
     * @param CustomerAddressEntity|OrderAddressEntity|null $address
     */
    private function getCardholderData(?Entity $address): CardholderData
    {
        if ($address === null) {
            throw new AddressNotFoundException('billing');
        }

        $data = new CardholderData();

        $data->setCardholderName($address->getFirstName() . ' ' . $address->getLastName());

        $state = $address->getCountryState();
        $country = $address->getCountry();

        $billingAddress = new BillingAddress();
        $billingAddress->setStreetAddress($address->getStreet());
        $billingAddress->setExtendedAddress($address->getAdditionalAddressLine1());
        $billingAddress->setRegion($state ? $state->getName() : '');
        $billingAddress->setLocality($address->getCity());
        $billingAddress->setPostalCode($address->getZipcode());
        $billingAddress->setCountryCodeAlpha2($country ? $country->getIso() : '');

        $data->setBillingAddress($billingAddress);

        return $data;
    }

    private function getButtonLanguage(SalesChannelContext $context): string
    {
        if ($settingsLocale = $this->systemConfigService->getString(Settings::SPB_BUTTON_LANGUAGE_ISO, $context->getSalesChannelId())) {
            return $settingsLocale;
        }

        return \str_replace(
            '-',
            '_',
            $this->localeCodeProvider->getLocaleCodeFromContext($context->getContext())
        );
    }
}
