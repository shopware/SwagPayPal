<?php declare(strict_types=1);
/*
 * (c) shopware AG <info@shopware.com>
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Swag\PayPal\Util\Lifecycle\Method;

use Swag\PayPal\Checkout\Payment\Method\SEPAHandler;
use Swag\PayPal\RestApi\V1\Api\MerchantIntegrations;
use Swag\PayPal\RestApi\V1\Api\MerchantIntegrations\Product;
use Swag\PayPal\Storefront\Data\CheckoutDataMethodInterface;
use Swag\PayPal\Storefront\Data\Service\AbstractCheckoutDataService;
use Swag\PayPal\Storefront\Data\Service\SEPACheckoutDataService;
use Swag\PayPal\Util\Availability\AvailabilityContext;

class SEPAMethodData extends AbstractMethodData implements CheckoutDataMethodInterface
{
    public const PAYPAL_SEPA_FIELD_DATA_EXTENSION_ID = 'payPalSEPAFieldData';

    public function getTranslations(): array
    {
        return [
            'de-DE' => [
                'description' => '',
                'name' => 'SEPA Lastschrift',
            ],
            'en-GB' => [
                'description' => '',
                'name' => 'SEPA direct debit',
            ],
        ];
    }

    public function getPosition(): int
    {
        return -97;
    }

    /**
     * @return class-string
     */
    public function getHandler(): string
    {
        return SEPAHandler::class;
    }

    public function isAvailable(AvailabilityContext $availabilityContext): bool
    {
        return $availabilityContext->getCurrencyCode() === 'EUR'
            && $availabilityContext->getBillingCountryCode() === 'DE';
    }

    public function getInitialState(): bool
    {
        return false;
    }

    public function getMediaFileName(): ?string
    {
        return 'sepa';
    }

    public function getCheckoutDataService(): AbstractCheckoutDataService
    {
        return $this->container->get(SEPACheckoutDataService::class);
    }

    public function getCheckoutTemplateExtensionId(): string
    {
        return self::PAYPAL_SEPA_FIELD_DATA_EXTENSION_ID;
    }

    public function validateCapability(MerchantIntegrations $merchantIntegrations): string
    {
        $product = $merchantIntegrations->getSpecificProduct('PPCP_STANDARD');
        if ($product !== null && (\in_array($product->getVettingStatus(), [Product::VETTING_STATUS_APPROVED, Product::VETTING_STATUS_SUBSCRIBED], true))) {
            return self::CAPABILITY_ACTIVE;
        }

        return self::CAPABILITY_INELIGIBLE;
    }
}
