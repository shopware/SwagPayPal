<?php declare(strict_types=1);
/*
 * (c) shopware AG <info@shopware.com>
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Swag\PayPal\Util\Lifecycle\Method;

use Swag\PayPal\RestApi\V1\Api\MerchantIntegrations;
use Swag\PayPal\RestApi\V1\Api\MerchantIntegrations\Product;
use Swag\PayPal\Util\Availability\AvailabilityContext;

class MyBankMethodData extends AbstractMethodData
{
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
