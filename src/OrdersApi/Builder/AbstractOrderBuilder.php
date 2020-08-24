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
use Swag\PayPal\PayPal\ApiV2\Api\Common\Address;
use Swag\PayPal\PayPal\ApiV2\Api\Order\ApplicationContext;
use Swag\PayPal\PayPal\ApiV2\Api\Order\Payer;
use Swag\PayPal\PayPal\ApiV2\Api\Order\Payer\Address as PayerAddress;
use Swag\PayPal\PayPal\ApiV2\Api\Order\Payer\Name;
use Swag\PayPal\PayPal\ApiV2\Api\Order\PurchaseUnit\Shipping;
use Swag\PayPal\PayPal\ApiV2\Api\Order\PurchaseUnit\Shipping\Address as ShippingAddress;
use Swag\PayPal\PayPal\ApiV2\PaymentIntentV2;
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

    public function __construct(SettingsServiceInterface $settingsService, PriceFormatter $priceFormatter)
    {
        $this->settingsService = $settingsService;
        $this->priceFormatter = $priceFormatter;
    }

    protected function getIntent(): string
    {
        // TODO PPI-4 - Get intent from settings
        // $intent = $this->settings->getIntentV2();
        $intent = PaymentIntentV2::CAPTURE;
        $this->validateIntent($intent);

        return $intent;
    }

    protected function createPayer(CustomerEntity $customer): Payer
    {
        $payer = new Payer();
        $payer->setEmailAddress($customer->getEmail());
        $name = new Name();
        $name->setGivenName($customer->getFirstName());
        $name->setSurname($customer->getLastName());
        $payer->setName($name);

        $billingAddress = $customer->getActiveBillingAddress();
        if ($billingAddress === null) {
            $billingAddress = $customer->getDefaultBillingAddress();
        }
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
            $shippingAddress = $customer->getDefaultShippingAddress();
        }
        if ($shippingAddress === null) {
            throw new AddressNotFoundException($customer->getDefaultShippingAddressId());
        }
        /** @var ShippingAddress $address */
        $address = $this->createAddress($shippingAddress, new ShippingAddress());
        $shipping = new Shipping();
        $shipping->setAddress($address);

        return $shipping;
    }

    /**
     * @throws PayPalSettingsInvalidException
     */
    private function validateIntent(string $intent): void
    {
        if (!\in_array($intent, PaymentIntentV2::INTENTS, true)) {
            throw new PayPalSettingsInvalidException('intentV2');
        }
    }

    private function getBrandName(SalesChannelContext $salesChannelContext, SwagPayPalSettingStruct $settings): string
    {
        $brandName = $settings->getBrandName();

        if ($brandName === null || $brandName === '') {
            $brandName = $salesChannelContext->getSalesChannel()->getName() ?? '';
        }

        return $brandName;
    }

    private function getLandingPageType(SwagPayPalSettingStruct $settings): string
    {
        $landingPageType = \strtoupper($settings->getLandingPage());
        if ($landingPageType !== ApplicationContext::LANDING_PAGE_TYPE_BILLING) {
            $landingPageType = ApplicationContext::LANDING_PAGE_TYPE_LOGIN;
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
}
