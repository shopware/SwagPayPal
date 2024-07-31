<?php declare(strict_types=1);
/*
 * (c) shopware AG <info@shopware.com>
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Swag\PayPal\Util\Lifecycle\Method;

use Swag\PayPal\RestApi\V1\Api\MerchantIntegrations;
use Swag\PayPal\Util\Availability\AvailabilityContext;

class SofortMethodData extends AbstractMethodData
{
    public function getTranslations(): array
    {
        return [
            'de-DE' => [
                'description' => '',
                'name' => 'Sofort',
            ],
            'en-GB' => [
                'description' => '',
                'name' => 'Sofort',
            ],
        ];
    }

    public function getPosition(): int
    {
        return -87;
    }

    public function getHandler(): string
    {
        return 'Swag\PayPal\Checkout\Payment\Method\SofortAPMHandler';
    }

    public function isAvailable(AvailabilityContext $availabilityContext): bool
    {
        return ($availabilityContext->getCurrencyCode() === 'EUR'
                && \in_array($availabilityContext->getBillingCountryCode(), ['AT', 'BE', 'DE', 'ES', 'NL'], true))
            || ($availabilityContext->getCurrencyCode() === 'GBP'
                && $availabilityContext->getBillingCountryCode() === 'GB');
    }

    public function getInitialState(): bool
    {
        return false;
    }

    public function getMediaFileName(): ?string
    {
        return 'apm_sofort';
    }

    public function validateCapability(MerchantIntegrations $merchantIntegrations): string
    {
        return 'sofort';
    }
}
