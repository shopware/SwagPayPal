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
 * @internal not used yet, missing Storefront fields
 */
#[Package('checkout')]
class BoletoBancarioMethodData extends AbstractMethodData
{
    public const TECHNICAL_NAME = 'swag_paypal_boleto';

    /**
     * @return array<string, array<string, string>>
     */
    public function getTranslations(): array
    {
        return [
            'de-DE' => [
                'description' => '',
                'name' => 'Boleto Bancário',
            ],
            'en-GB' => [
                'description' => '',
                'name' => 'Boleto Bancário',
            ],
        ];
    }

    public function getPosition(): int
    {
        return -95;
    }

    public function getHandler(): string
    {
        return 'Swag\PayPal\Checkout\Payment\Method\BoletoBancarioAPMHandler';
    }

    public function getTechnicalName(): string
    {
        return self::TECHNICAL_NAME;
    }

    public function isAvailable(AvailabilityContext $availabilityContext): bool
    {
        return $availabilityContext->getTotalAmount() <= 35000.0
            && $availabilityContext->getCurrencyCode() === 'BRL'
            && $availabilityContext->getBillingCountryCode() === 'BR';
    }

    public function getInitialState(): bool
    {
        return true;
    }

    public function getMediaFileName(): ?string
    {
        return 'apm_boletobancario';
    }

    public function validateCapability(MerchantIntegrations $merchantIntegrations): string
    {
        return self::CAPABILITY_ACTIVE;
    }
}
