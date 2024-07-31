<?php declare(strict_types=1);
/*
 * (c) shopware AG <info@shopware.com>
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Swag\PayPal\Util\Lifecycle\Method;

use Swag\PayPal\RestApi\V1\Api\MerchantIntegrations;
use Swag\PayPal\Util\Availability\AvailabilityContext;

class OxxoMethodData extends AbstractMethodData
{
    public function getTranslations(): array
    {
        return [
            'de-DE' => [
                'description' => '',
                'name' => 'OXXO',
            ],
            'en-GB' => [
                'description' => '',
                'name' => 'OXXO',
            ],
        ];
    }

    public function getPosition(): int
    {
        return -89;
    }

    public function getHandler(): string
    {
        return 'Swag\PayPal\Checkout\Payment\Method\OxxoAPMHandler';
    }

    public function isAvailable(AvailabilityContext $availabilityContext): bool
    {
        return $availabilityContext->getTotalAmount() <= 10000.0
            && $availabilityContext->getCurrencyCode() === 'MXN'
            && $availabilityContext->getBillingCountryCode() === 'MX';
    }

    public function getInitialState(): bool
    {
        return true;
    }

    public function getMediaFileName(): ?string
    {
        return 'apm_oxxo';
    }

    public function validateCapability(MerchantIntegrations $merchantIntegrations): string
    {
        return self::CAPABILITY_ACTIVE;
    }
}
