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
use Swag\PayPal\OrdersApi\Builder\Util\AddressProvider;
use Swag\PayPal\OrdersApi\Builder\Util\PurchaseUnitProvider;
use Swag\PayPal\RestApi\V2\Api\Common\Address;
use Swag\PayPal\RestApi\V2\Api\Order\ApplicationContext;
use Swag\PayPal\RestApi\V2\Api\Order\Payer;
use Swag\PayPal\RestApi\V2\Api\Order\Payer\Address as PayerAddress;
use Swag\PayPal\RestApi\V2\Api\Order\Payer\Name as PayerName;
use Swag\PayPal\RestApi\V2\PaymentIntentV2;
use Swag\PayPal\Setting\Exception\PayPalSettingsInvalidException;
use Swag\PayPal\Setting\Settings;

abstract class AbstractOrderBuilder
{
    protected SystemConfigService $systemConfigService;

    protected PurchaseUnitProvider $purchaseUnitProvider;

    protected ?AddressProvider $addressProvider;

    /**
     * @deprecated tag:v5.0.0 - parameter $addressProvider will be required
     */
    public function __construct(
        SystemConfigService $systemConfigService,
        PurchaseUnitProvider $purchaseUnitProvider,
        ?AddressProvider $addressProvider = null
    ) {
        $this->systemConfigService = $systemConfigService;
        $this->purchaseUnitProvider = $purchaseUnitProvider;
        $this->addressProvider = $addressProvider;
    }

    /**
     * @throws PayPalSettingsInvalidException
     */
    protected function getIntent(string $salesChannelId): string
    {
        $intent = $this->systemConfigService->getString(Settings::INTENT, $salesChannelId);

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
        $address = new PayerAddress();
        if ($this->addressProvider !== null) {
            $this->addressProvider->createAddress($billingAddress, $address);
        } else {
            $this->createAddress($billingAddress, $address);
        }
        $payer->setAddress($address);

        return $payer;
    }

    protected function createApplicationContext(
        SalesChannelContext $salesChannelContext
    ): ApplicationContext {
        $applicationContext = new ApplicationContext();
        $applicationContext->setBrandName($this->getBrandName($salesChannelContext));
        $applicationContext->setLandingPage($this->getLandingPageType($salesChannelContext->getSalesChannelId()));

        return $applicationContext;
    }

    protected function getBrandName(SalesChannelContext $salesChannelContext): string
    {
        $brandName = $this->systemConfigService->getString(Settings::BRAND_NAME, $salesChannelContext->getSalesChannelId());

        if ($brandName === '') {
            $brandName = $salesChannelContext->getSalesChannel()->getTranslation('name') ?? '';
        }

        return $brandName;
    }

    /**
     * @throws PayPalSettingsInvalidException
     */
    private function getLandingPageType(string $salesChannelId): string
    {
        $landingPageType = $this->systemConfigService->getString(Settings::LANDING_PAGE, $salesChannelId);

        if (!\in_array($landingPageType, ApplicationContext::LANDING_PAGE_TYPES, true)) {
            throw new PayPalSettingsInvalidException('landingPage');
        }

        return $landingPageType;
    }

    /**
     * @deprecated tag:v5.0.0 - will be removed, use AddressProvider instead
     */
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
