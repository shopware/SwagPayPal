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
class MyBankMethodData extends AbstractMethodData
{
    public const TECHNICAL_NAME = 'swag_paypal_my_bank';

    /**
     * @return array<string, array<string, string>>
     */
    public function getTranslations(): array
    {
        return [
            'de-DE' => [
                'description' => '',
                'name' => 'MyBank',
            ],
            'en-GB' => [
                'description' => '',
                'name' => 'MyBank',
            ],
        ];
    }

    public function getPosition(): int
    {
        return -90;
    }

    public function getHandler(): string
    {
        return 'Swag\PayPal\Checkout\Payment\Method\MyBankAPMHandler';
    }

    public function getTechnicalName(): string
    {
        return self::TECHNICAL_NAME;
    }

    public function isAvailable(AvailabilityContext $availabilityContext): bool
    {
        return $availabilityContext->getCurrencyCode() === 'EUR'
            && $availabilityContext->getBillingCountryCode() === 'IT';
    }

    public function getInitialState(): bool
    {
        return false;
    }

    public function getMediaFileName(): ?string
    {
        return 'apm_mybank';
    }

    public function validateCapability(MerchantIntegrations $merchantIntegrations): string
    {
        return 'mybank';
    }
}
