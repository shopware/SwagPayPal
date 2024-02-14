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
class P24MethodData extends AbstractMethodData
{
    public const TECHNICAL_NAME = 'swag_paypal_p24';

    /**
     * @return array<string, array<string, string>>
     */
    public function getTranslations(): array
    {
        return [
            'de-DE' => [
                'description' => '',
                'name' => 'Przelewy24',
            ],
            'en-GB' => [
                'description' => '',
                'name' => 'Przelewy24',
            ],
        ];
    }

    public function getPosition(): int
    {
        return -88;
    }

    public function getHandler(): string
    {
        return 'Swag\PayPal\Checkout\Payment\Method\P24APMHandler';
    }

    public function getTechnicalName(): string
    {
        return self::TECHNICAL_NAME;
    }

    public function isAvailable(AvailabilityContext $availabilityContext): bool
    {
        return $availabilityContext->getTotalAmount() >= 1.0
            && \in_array($availabilityContext->getCurrencyCode(), ['EUR', 'PLN'], true)
            && $availabilityContext->getBillingCountryCode() === 'PL';
    }

    public function getInitialState(): bool
    {
        return true;
    }

    public function getMediaFileName(): ?string
    {
        return 'apm_p24';
    }

    public function validateCapability(MerchantIntegrations $merchantIntegrations): string
    {
        return self::CAPABILITY_ACTIVE;
    }
}
