<?php declare(strict_types=1);
/*
 * (c) shopware AG <info@shopware.com>
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Swag\PayPal\Util\Lifecycle\Method;

use Shopware\Core\Framework\Log\Package;
use Shopware\Core\System\SalesChannel\SalesChannelContext;
use Shopware\Core\System\SystemConfig\SystemConfigService;
use Swag\PayPal\Checkout\Payment\Method\VenmoHandler;
use Swag\PayPal\RestApi\V1\Api\MerchantIntegrations;
use Swag\PayPal\RestApi\V1\Api\MerchantIntegrations\Capability;
use Swag\PayPal\Setting\Settings;
use Swag\PayPal\Storefront\Data\CheckoutDataMethodInterface;
use Swag\PayPal\Storefront\Data\Service\AbstractCheckoutDataService;
use Swag\PayPal\Storefront\Data\Service\VenmoCheckoutDataService;
use Swag\PayPal\Util\Availability\AvailabilityContext;

/**
 * @internal
 */
#[Package('checkout')]
class VenmoMethodData extends AbstractMethodData implements CheckoutDataMethodInterface
{
    public const TECHNICAL_NAME = 'swag_paypal_venmo';

    public const PAYPAL_VENMO_FIELD_DATA_EXTENSION_ID = 'payPalVenmoFieldData';

    /**
     * @return array<string, array<string, string>>
     */
    public function getTranslations(): array
    {
        return [
            'de-DE' => [
                'description' => '',
                'name' => 'Venmo',
            ],
            'en-GB' => [
                'description' => '',
                'name' => 'Venmo',
            ],
        ];
    }

    public function getPosition(): int
    {
        return -96;
    }

    /**
     * @return class-string
     */
    public function getHandler(): string
    {
        return VenmoHandler::class;
    }

    public function getTechnicalName(): string
    {
        return self::TECHNICAL_NAME;
    }

    public function isAvailable(AvailabilityContext $availabilityContext): bool
    {
        if ($availabilityContext->getCurrencyCode() !== 'USD'
            || $availabilityContext->getBillingCountryCode() !== 'US') {
            return false;
        }

        if ($availabilityContext->isSubscription()) {
            $systemConfigService = $this->container->get(SystemConfigService::class);

            return $systemConfigService->getBool(Settings::VAULTING_ENABLED_VENMO, $availabilityContext->getSalesChannelId());
        }

        return true;
    }

    public function getInitialState(): bool
    {
        return false;
    }

    public function getMediaFileName(): ?string
    {
        return 'venmo';
    }

    public function getCheckoutDataService(): AbstractCheckoutDataService
    {
        return $this->container->get(VenmoCheckoutDataService::class);
    }

    public function getCheckoutTemplateExtensionId(): string
    {
        return self::PAYPAL_VENMO_FIELD_DATA_EXTENSION_ID;
    }

    public function validateCapability(MerchantIntegrations $merchantIntegrations): string
    {
        $capability = $merchantIntegrations->getSpecificCapability('VENMO_PAY_PROCESSING');
        if ($capability !== null && $capability->getStatus() === Capability::STATUS_ACTIVE) {
            return self::CAPABILITY_ACTIVE;
        }

        return self::CAPABILITY_INELIGIBLE;
    }

    public function isVaultable(SalesChannelContext $context): bool
    {
        $systemConfigService = $this->container->get(SystemConfigService::class);

        return $systemConfigService->getBool(Settings::VAULTING_ENABLED_VENMO, $context->getSalesChannelId());
    }
}
