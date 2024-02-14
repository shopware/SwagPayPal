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
class BlikMethodData extends AbstractMethodData
{
    public const TECHNICAL_NAME = 'swag_paypal_blik';

    /**
     * @return array<string, array<string, string>>
     */
    public function getTranslations(): array
    {
        return [
            'de-DE' => [
                'description' => '',
                'name' => 'BLIK',
            ],
            'en-GB' => [
                'description' => '',
                'name' => 'BLIK',
            ],
        ];
    }

    public function getPosition(): int
    {
        return -96;
    }

    public function getHandler(): string
    {
        return 'Swag\PayPal\Checkout\Payment\Method\BlikAPMHandler';
    }

    public function getTechnicalName(): string
    {
        return self::TECHNICAL_NAME;
    }

    public function isAvailable(AvailabilityContext $availabilityContext): bool
    {
        return $availabilityContext->getTotalAmount() >= 1.0
            && $availabilityContext->getCurrencyCode() === 'PLN'
            && $availabilityContext->getBillingCountryCode() === 'PL';
    }

    public function getInitialState(): bool
    {
        return true;
    }

    public function getMediaFileName(): ?string
    {
        return 'apm_blik';
    }

    public function validateCapability(MerchantIntegrations $merchantIntegrations): string
    {
        return self::CAPABILITY_ACTIVE;
    }
}
