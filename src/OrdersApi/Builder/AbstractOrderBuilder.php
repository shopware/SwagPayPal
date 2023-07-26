<?php declare(strict_types=1);
/*
 * (c) shopware AG <info@shopware.com>
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Swag\PayPal\OrdersApi\Builder;

use Shopware\Core\Checkout\Customer\CustomerEntity;
use Shopware\Core\Checkout\Customer\Exception\AddressNotFoundException;
use Shopware\Core\Framework\Log\Package;
use Shopware\Core\System\SalesChannel\SalesChannelContext;
use Shopware\Core\System\SystemConfig\SystemConfigService;
use Swag\PayPal\Checkout\SalesChannel\CreateOrderRoute;
use Swag\PayPal\OrdersApi\Builder\Util\AddressProvider;
use Swag\PayPal\OrdersApi\Builder\Util\PurchaseUnitProvider;
use Swag\PayPal\RestApi\V2\Api\Common\Address;
use Swag\PayPal\RestApi\V2\Api\Common\Name;
use Swag\PayPal\RestApi\V2\Api\Order\ApplicationContext;
use Swag\PayPal\RestApi\V2\Api\Order\Payer;
use Swag\PayPal\RestApi\V2\Api\Order\PaymentSource\Common\ExperienceContext;
use Swag\PayPal\RestApi\V2\PaymentIntentV2;
use Swag\PayPal\Setting\Exception\PayPalSettingsInvalidException;
use Swag\PayPal\Setting\Settings;
use Swag\PayPal\Util\LocaleCodeProvider;

#[Package('checkout')]
abstract class AbstractOrderBuilder
{
    /**
     * @internal
     */
    public function __construct(
        protected readonly SystemConfigService $systemConfigService,
        protected readonly PurchaseUnitProvider $purchaseUnitProvider,
        protected readonly AddressProvider $addressProvider,
        protected readonly LocaleCodeProvider $localeCodeProvider,
    ) {
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

    /**
     * @deprecated tag:v9.0.0 - will be removed, use payment source attributes instead
     */
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
            throw new AddressNotFoundException($customer->getDefaultBillingAddressId());
        }
        $address = new Address();
        $this->addressProvider->createAddress($billingAddress, $address);
        $payer->setAddress($address);

        return $payer;
    }

    /**
     * @deprecated tag:v9.0.0 - will be removed, use experience context instead
     */
    protected function createApplicationContext(
        SalesChannelContext $salesChannelContext
    ): ApplicationContext {
        $applicationContext = new ApplicationContext();
        $applicationContext->setBrandName($this->getBrandName($salesChannelContext));
        $applicationContext->setLandingPage($this->getLandingPageType($salesChannelContext->getSalesChannelId()));

        return $applicationContext;
    }

    protected function createExperienceContext(
        SalesChannelContext $salesChannelContext
    ): ExperienceContext {
        $experienceContext = new ExperienceContext();
        $experienceContext->setBrandName($this->getBrandName($salesChannelContext));
        $experienceContext->setLocale($this->localeCodeProvider->getLocaleCodeFromContext($salesChannelContext->getContext()));
        $experienceContext->setLandingPage($this->getLandingPageType($salesChannelContext->getSalesChannelId()));
        $experienceContext->setReturnUrl(CreateOrderRoute::FAKE_URL);
        $experienceContext->setCancelUrl(CreateOrderRoute::FAKE_URL . '?cancel=1');

        return $experienceContext;
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
}
