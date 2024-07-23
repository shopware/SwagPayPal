<?php declare(strict_types=1);
/*
 * (c) shopware AG <info@shopware.com>
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Swag\PayPal\Util\Lifecycle\Method;

use Shopware\Core\Framework\Log\Package;
use Swag\PayPal\Checkout\Payment\Method\ApplePayHandler;
use Swag\PayPal\RestApi\V1\Api\MerchantIntegrations;
use Swag\PayPal\Storefront\Data\CheckoutDataMethodInterface;
use Swag\PayPal\Storefront\Data\Service\AbstractCheckoutDataService;
use Swag\PayPal\Storefront\Data\Service\ApplePayCheckoutDataService;
use Swag\PayPal\Util\Availability\AvailabilityContext;

/**
 * @internal
 */
#[Package('checkout')]
class ApplePayMethodData extends AbstractMethodData implements CheckoutDataMethodInterface
{
    public const TECHNICAL_NAME = 'swag_paypal_apple_pay';

    public const PAYPAL_APPLE_PAY_FIELD_DATA_EXTENSION_ID = 'payPalApplePayFieldData';

    public const AVAILABLE_COUNTRIES = ['AU', 'AT', 'BE', 'BG', 'CA', 'CY', 'CZ', 'DK', 'EE', 'FI', 'FR', 'DE', 'GR', 'HU', 'IE', 'IT', 'LV', 'LI', 'LT', 'LU', 'MT', 'NL', 'NO', 'PL', 'PT', 'RO', 'SK', 'SI', 'ES', 'SE', 'US', 'GB'];

    public const AVAILABLE_CURRENCIES = ['AUD', 'BRL', 'CAD', 'CHF', 'CZK', 'DKK', 'EUR', 'GBP', 'HKD', 'HUF', 'ILS', 'JPY', 'MXN', 'NOK', 'NZD', 'PHP', 'PLN', 'SEK', 'SGD', 'THB', 'TWD', 'USD'];

    /**
     * @return array<string, array<string, string>>
     */
    public function getTranslations(): array
    {
        return [
            'de-DE' => [
                'description' => '',
                'name' => 'Apple Pay',
            ],
            'en-GB' => [
                'description' => '',
                'name' => 'Apple Pay',
            ],
        ];
    }

    public function getPosition(): int
    {
        return -98;
    }

    /**
     * @return class-string
     */
    public function getHandler(): string
    {
        return ApplePayHandler::class;
    }

    public function getTechnicalName(): string
    {
        return self::TECHNICAL_NAME;
    }

    public function isAvailable(AvailabilityContext $availabilityContext): bool
    {
        return \in_array($availabilityContext->getCurrencyCode(), self::AVAILABLE_CURRENCIES, true)
            && \in_array($availabilityContext->getBillingCountryCode(), self::AVAILABLE_COUNTRIES, true);
    }

    public function getInitialState(): bool
    {
        return false;
    }

    public function getMediaFileName(): ?string
    {
        return 'apple_pay';
    }

    public function getCheckoutDataService(): AbstractCheckoutDataService
    {
        return $this->container->get(ApplePayCheckoutDataService::class);
    }

    public function getCheckoutTemplateExtensionId(): string
    {
        return self::PAYPAL_APPLE_PAY_FIELD_DATA_EXTENSION_ID;
    }

    public function validateCapability(MerchantIntegrations $merchantIntegrations): string
    {
        $capability = $merchantIntegrations->getSpecificCapability('APPLE_PAY');

        if ($capability === null) {
            return self::CAPABILITY_INACTIVE;
        }

        if ($capability->getStatus() === 'ACTIVE') {
            return self::CAPABILITY_ACTIVE;
        }

        return self::CAPABILITY_INELIGIBLE;
    }
}
