<?php declare(strict_types=1);
/*
 * (c) shopware AG <info@shopware.com>
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Swag\PayPal\OrdersApi\Builder;

use Shopware\Core\Checkout\Customer\Aggregate\CustomerAddress\CustomerAddressEntity;
use Shopware\Core\Checkout\Customer\CustomerEntity;
use Shopware\Core\Checkout\Customer\Exception\AddressNotFoundException;
use Shopware\Core\System\SalesChannel\SalesChannelContext;
use Shopware\Core\System\SystemConfig\SystemConfigService;
use Swag\PayPal\OrdersApi\Builder\Util\AmountProvider;
use Swag\PayPal\OrdersApi\Builder\Util\PurchaseUnitProvider;
use Swag\PayPal\RestApi\V2\Api\Common\Address;
use Swag\PayPal\RestApi\V2\Api\Order\ApplicationContext;
use Swag\PayPal\RestApi\V2\Api\Order\Payer;
use Swag\PayPal\RestApi\V2\Api\Order\Payer\Address as PayerAddress;
use Swag\PayPal\RestApi\V2\Api\Order\Payer\Name as PayerName;
use Swag\PayPal\RestApi\V2\Api\Order\PurchaseUnit\Shipping;
use Swag\PayPal\RestApi\V2\Api\Order\PurchaseUnit\Shipping\Address as ShippingAddress;
use Swag\PayPal\RestApi\V2\Api\Order\PurchaseUnit\Shipping\Name as ShippingName;
use Swag\PayPal\RestApi\V2\PaymentIntentV2;
use Swag\PayPal\Setting\Exception\PayPalSettingsInvalidException;
use Swag\PayPal\Setting\Service\SettingsServiceInterface;
use Swag\PayPal\Setting\Settings;
use Swag\PayPal\Setting\SwagPayPalSettingStruct;
use Swag\PayPal\Util\PriceFormatter;

abstract class AbstractOrderBuilder
{
    /**
     * @deprecated tag:v4.0.0 - will be removed and moved as private to OrderFromCartBuilder
     *
     * @var PriceFormatter
     */
    protected $priceFormatter;

    /**
     * @deprecated tag:v4.0.0 - will be removed
     *
     * @var SettingsServiceInterface
     */
    protected $settingsService;

    /**
     * @deprecated tag:v4.0.0 - will be removed
     *
     * @var AmountProvider
     */
    protected $amountProvider;

    /**
     * @deprecated tag:v4.0.0 - will not be nullable
     */
    protected ?SystemConfigService $systemConfigService;

    /**
     * @deprecated tag:v4.0.0 - will not be nullable
     */
    protected ?PurchaseUnitProvider $purchaseUnitProvider;

    /**
     * @deprecated tag:v4.0.0 - parameter $settingsService, $priceFormatter and $amountProvider will be removed,
     *                          parameter $systemConfigService and $purchaseUnitProvider will not be nullable
     */
    public function __construct(
        SettingsServiceInterface $settingsService,
        PriceFormatter $priceFormatter,
        AmountProvider $amountProvider,
        ?SystemConfigService $systemConfigService = null,
        ?PurchaseUnitProvider $purchaseUnitProvider = null
    ) {
        $this->settingsService = $settingsService;
        $this->priceFormatter = $priceFormatter;
        $this->amountProvider = $amountProvider;
        $this->systemConfigService = $systemConfigService;
        $this->purchaseUnitProvider = $purchaseUnitProvider;
    }

    /**
     * @deprecated tag:v4.0.0 - parameter $settings will be removed, parameter $salesChannelId will be not nullable
     *
     * @throws PayPalSettingsInvalidException
     */
    protected function getIntent(?SwagPayPalSettingStruct $settings = null, ?string $salesChannelId = null): string
    {
        $intent = PaymentIntentV2::CAPTURE;
        if ($settings !== null) {
            $intent = $settings->getIntent();
        }

        if ($this->systemConfigService !== null) {
            $intent = $this->systemConfigService->getString(Settings::INTENT, $salesChannelId);
        }

        if (!\in_array($intent, PaymentIntentV2::INTENTS, true)) {
            throw new PayPalSettingsInvalidException('intent');
        }

        return $intent;
    }

    protected function createPayer(CustomerEntity $customer): Payer
    {
        $payer = new Payer();
        $payer->setEmailAddress($customer->getEmail());
        $name = new PayerName();
        $name->setGivenName($customer->getFirstName());
        $name->setSurname($customer->getLastName());
        $payer->setName($name);

        $billingAddress = $customer->getActiveBillingAddress();
        if ($billingAddress === null) {
            throw new AddressNotFoundException($customer->getDefaultBillingAddressId());
        }
        /** @var PayerAddress $address */
        $address = $this->createAddress($billingAddress, new PayerAddress());
        $payer->setAddress($address);

        return $payer;
    }

    /**
     * @deprecated tag:v4.0.0 - parameter $settings will be removed
     */
    protected function createApplicationContext(
        SalesChannelContext $salesChannelContext,
        ?SwagPayPalSettingStruct $settings = null
    ): ApplicationContext {
        $applicationContext = new ApplicationContext();
        $applicationContext->setBrandName($this->getBrandName($salesChannelContext, $settings, $salesChannelContext->getSalesChannelId()));
        $applicationContext->setLandingPage($this->getLandingPageType($settings, $salesChannelContext->getSalesChannelId()));

        return $applicationContext;
    }

    /**
     * @deprecated tag:v4.0.0 - will be removed, is part of Swag\PayPal\OrdersApi\Builder\Util\PurchaseUnitProvider now
     */
    protected function createShipping(CustomerEntity $customer): Shipping
    {
        $shippingAddress = $customer->getActiveShippingAddress();
        if ($shippingAddress === null) {
            throw new AddressNotFoundException($customer->getDefaultShippingAddressId());
        }

        $shipping = new Shipping();

        /** @var ShippingAddress $address */
        $address = $this->createAddress($shippingAddress, new ShippingAddress());
        $shipping->setAddress($address);
        $shipping->setName($this->createShippingName($shippingAddress));

        return $shipping;
    }

    /**
     * @deprecated tag:v4.0.0 - parameter $settings will be removed, parameter $salesChannelId will be not nullable
     */
    private function getBrandName(SalesChannelContext $salesChannelContext, ?SwagPayPalSettingStruct $settings, ?string $salesChannelId): string
    {
        $brandName = null;
        if ($settings !== null) {
            $brandName = $settings->getBrandName();
        }

        if ($this->systemConfigService !== null) {
            $brandName = $this->systemConfigService->getString(Settings::BRAND_NAME, $salesChannelId);
        }

        if ($brandName === null || $brandName === '') {
            $brandName = $salesChannelContext->getSalesChannel()->getName() ?? '';
        }

        return $brandName;
    }

    /**
     * @deprecated tag:v4.0.0 - parameter $settings will be removed, parameter $salesChannelId will be not nullable
     *
     * @throws PayPalSettingsInvalidException
     */
    private function getLandingPageType(?SwagPayPalSettingStruct $settings, ?string $salesChannelId): string
    {
        $landingPageType = ApplicationContext::LANDING_PAGE_TYPE_NO_PREFERENCE;
        if ($settings !== null) {
            $landingPageType = $settings->getLandingPage();
        }

        if ($this->systemConfigService !== null) {
            $landingPageType = $this->systemConfigService->getString(Settings::LANDING_PAGE, $salesChannelId);
        }

        if (!\in_array($landingPageType, ApplicationContext::LANDING_PAGE_TYPES, true)) {
            throw new PayPalSettingsInvalidException('landingPage');
        }

        return $landingPageType;
    }

    private function createAddress(CustomerAddressEntity $customerAddress, Address $address): Address
    {
        $address->setAddressLine1($customerAddress->getStreet());

        $additionalAddressLine1 = $customerAddress->getAdditionalAddressLine1();
        if ($additionalAddressLine1 !== null) {
            $address->setAddressLine2($additionalAddressLine1);
        }

        $state = $customerAddress->getCountryState();
        if ($state !== null) {
            $address->setAdminArea1($state->getShortCode());
        }

        $address->setAdminArea2($customerAddress->getCity());
        $address->setPostalCode($customerAddress->getZipcode());

        $country = $customerAddress->getCountry();
        if ($country !== null) {
            $countryIso = $country->getIso();
            if ($countryIso !== null) {
                $address->setCountryCode($countryIso);
            }
        }

        return $address;
    }

    private function createShippingName(CustomerAddressEntity $shippingAddress): ShippingName
    {
        $shippingName = new ShippingName();
        $shippingName->setFullName(\sprintf('%s %s', $shippingAddress->getFirstName(), $shippingAddress->getLastName()));

        return $shippingName;
    }
}
