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
use Swag\PayPal\OrdersApi\Builder\Util\AmountProvider;
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
use Swag\PayPal\Setting\SwagPayPalSettingStruct;
use Swag\PayPal\Util\PriceFormatter;

abstract class AbstractOrderBuilder
{
    /**
     * @var PriceFormatter
     */
    protected $priceFormatter;

    /**
     * @var SettingsServiceInterface
     */
    protected $settingsService;

    /**
     * @var AmountProvider
     */
    protected $amountProvider;

    public function __construct(
        SettingsServiceInterface $settingsService,
        PriceFormatter $priceFormatter,
        AmountProvider $amountProvider
    ) {
        $this->settingsService = $settingsService;
        $this->priceFormatter = $priceFormatter;
        $this->amountProvider = $amountProvider;
    }

    /**
     * @throws PayPalSettingsInvalidException
     */
    protected function getIntent(SwagPayPalSettingStruct $settings): string
    {
        $intent = $settings->getIntent();
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

    protected function createApplicationContext(
        SalesChannelContext $salesChannelContext,
        SwagPayPalSettingStruct $settings
    ): ApplicationContext {
        $applicationContext = new ApplicationContext();
        $applicationContext->setBrandName($this->getBrandName($salesChannelContext, $settings));
        $applicationContext->setLandingPage($this->getLandingPageType($settings));

        return $applicationContext;
    }

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

    private function getBrandName(SalesChannelContext $salesChannelContext, SwagPayPalSettingStruct $settings): string
    {
        $brandName = $settings->getBrandName();

        if ($brandName === null || $brandName === '') {
            $brandName = $salesChannelContext->getSalesChannel()->getName() ?? '';
        }

        return $brandName;
    }

    /**
     * @throws PayPalSettingsInvalidException
     */
    private function getLandingPageType(SwagPayPalSettingStruct $settings): string
    {
        $landingPageType = $settings->getLandingPage();
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
