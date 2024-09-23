<?php declare(strict_types=1);
/*
 * (c) shopware AG <info@shopware.com>
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Swag\PayPal\Util\Lifecycle\Method;

use Shopware\Core\Framework\Log\Package;
use Swag\PayPal\RestApi\V1\Api\MerchantIntegrations;
use Swag\PayPal\Util\Availability\AvailabilityContext;

/**
 * @internal
 */
#[Package('checkout')]
class TrustlyMethodData extends AbstractMethodData
{
    public const TECHNICAL_NAME = 'swag_paypal_trustly';

    public const AVAILABLE_COUNTRIES = ['AT', 'DE', 'DK', 'EE', 'ES', 'FI', 'GB', 'LT', 'LV', 'NL', 'NO', 'SE'];

    public const AVAILABLE_CURRENCIES = ['EUR', 'DKK', 'SEK', 'GBP', 'NOK'];

    /**
     * @return array<string, array<string, string>>
     */
    public function getTranslations(): array
    {
        return [
            'de-DE' => [
                'description' => '',
                'name' => 'Trustly',
            ],
            'en-GB' => [
                'description' => '',
                'name' => 'Trustly',
            ],
        ];
    }

    public function getPosition(): int
    {
        return -86;
    }

    public function getHandler(): string
    {
        return 'Swag\PayPal\Checkout\Payment\Method\TrustlyAPMHandler';
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
        return 'apm_trustly';
    }

    public function validateCapability(MerchantIntegrations $merchantIntegrations): string
    {
        return self::CAPABILITY_ACTIVE;
    }
}
