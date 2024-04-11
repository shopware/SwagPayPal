<?php declare(strict_types=1);
/*
 * (c) shopware AG <info@shopware.com>
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Swag\PayPal\Util\Lifecycle\Method;

use Shopware\Core\Framework\Log\Package;
use Swag\PayPal\Checkout\Payment\Method\PUIHandler;
use Swag\PayPal\RestApi\V1\Api\MerchantIntegrations;
use Swag\PayPal\RestApi\V1\Api\MerchantIntegrations\Capability;
use Swag\PayPal\RestApi\V1\Api\MerchantIntegrations\Product;
use Swag\PayPal\Util\Availability\AvailabilityContext;

/**
 * @internal
 */
#[Package('checkout')]
class PUIMethodData extends AbstractMethodData
{
    public const TECHNICAL_NAME = 'swag_paypal_pui';

    /**
     * @return array<string, array<string, string>>
     */
    public function getTranslations(): array
    {
        return [
            'de-DE' => [
                'name' => 'Rechnungskauf',
                'description' => 'Kaufen Sie ganz bequem auf Rechnung und bezahlen Sie später.',
            ],
            'en-GB' => [
                'name' => 'Pay upon invoice',
                'description' => 'Buy comfortably on invoice and pay later.',
            ],
        ];
    }

    public function getPosition(): int
    {
        return -99;
    }

    /**
     * @return class-string
     */
    public function getHandler(): string
    {
        return PUIHandler::class;
    }

    public function getTechnicalName(): string
    {
        return self::TECHNICAL_NAME;
    }

    public function isAvailable(AvailabilityContext $availabilityContext): bool
    {
        return $availabilityContext->getTotalAmount() >= 5.0
            && $availabilityContext->getTotalAmount() <= 2500.0
            && $availabilityContext->getCurrencyCode() === 'EUR'
            && $availabilityContext->getBillingCountryCode() === 'DE'
            && !$availabilityContext->hasDigitalProducts();
    }

    public function getInitialState(): bool
    {
        return false;
    }

    public function getMediaFileName(): ?string
    {
        return 'pui';
    }

    public function validateCapability(MerchantIntegrations $merchantIntegrations): string
    {
        $product = $merchantIntegrations->getSpecificProduct('PAYMENT_METHODS');
        if ($product === null || (!\in_array($product->getVettingStatus(), [Product::VETTING_STATUS_APPROVED, Product::VETTING_STATUS_SUBSCRIBED], true))) {
            return self::CAPABILITY_INELIGIBLE;
        }

        $capability = $merchantIntegrations->getSpecificCapability('PAY_UPON_INVOICE');
        if ($capability !== null && $capability->getStatus() === Capability::STATUS_ACTIVE) {
            return self::CAPABILITY_ACTIVE;
        }

        return self::CAPABILITY_INELIGIBLE;
    }
}
