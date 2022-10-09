<?php declare(strict_types=1);
/*
 * (c) shopware AG <info@shopware.com>
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Swag\PayPal\Util\Lifecycle\Method;

use Swag\PayPal\Checkout\Payment\Method\VenmoHandler;
use Swag\PayPal\RestApi\V1\Api\MerchantIntegrations;
use Swag\PayPal\RestApi\V1\Api\MerchantIntegrations\Capability;
use Swag\PayPal\Storefront\Data\CheckoutDataMethodInterface;
use Swag\PayPal\Storefront\Data\Service\AbstractCheckoutDataService;
use Swag\PayPal\Storefront\Data\Service\VenmoCheckoutDataService;
use Swag\PayPal\Util\Availability\AvailabilityContext;

class VenmoMethodData extends AbstractMethodData implements CheckoutDataMethodInterface
{
    public const PAYPAL_VENMO_FIELD_DATA_EXTENSION_ID = 'payPalVenmoFieldData';

    public function getTranslations(): array
    {
        return [
            'de-DE' => [
                'description' => '',
                'name' => 'Venmo',
            ],
            'en-GB' => [
                'description' => '',
                'name' => 'Venmo',
            ],
        ];
    }

    public function getPosition(): int
    {
        return -96;
    }

    /**
     * @return class-string
     */
    public function getHandler(): string
    {
        return VenmoHandler::class;
    }

    public function isAvailable(AvailabilityContext $availabilityContext): bool
    {
        return $availabilityContext->getCurrencyCode() === 'USD'
            && $availabilityContext->getBillingCountryCode() === 'US';
    }

    public function getInitialState(): bool
    {
        return false;
    }

    public function getMediaFileName(): ?string
    {
        return 'venmo';
    }

    public function getCheckoutDataService(): AbstractCheckoutDataService
    {
        return $this->container->get(VenmoCheckoutDataService::class);
    }

    public function getCheckoutTemplateExtensionId(): string
    {
        return self::PAYPAL_VENMO_FIELD_DATA_EXTENSION_ID;
    }

    public function validateCapability(MerchantIntegrations $merchantIntegrations): string
    {
        $capability = $merchantIntegrations->getSpecificCapability('VENMO_PAY_PROCESSING');
        if ($capability !== null && $capability->getStatus() === Capability::STATUS_ACTIVE) {
            return self::CAPABILITY_ACTIVE;
        }

        return self::CAPABILITY_INELIGIBLE;
    }
}
