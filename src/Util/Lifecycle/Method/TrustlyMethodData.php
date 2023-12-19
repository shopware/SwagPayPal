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

#[Package('checkout')]
class TrustlyMethodData extends AbstractMethodData
{
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

    public function isAvailable(AvailabilityContext $availabilityContext): bool
    {
        return ($availabilityContext->getCurrencyCode() === 'EUR'
                && \in_array($availabilityContext->getBillingCountryCode(), ['EE', 'FI', 'NL'], true))
            || (\in_array($availabilityContext->getCurrencyCode(), ['EUR', 'SEK'], true)
                && $availabilityContext->getBillingCountryCode() === 'SE');
    }

    public function getInitialState(): bool
    {
        return true;
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
