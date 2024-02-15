<?php declare(strict_types=1);
/*
 * (c) shopware AG <info@shopware.com>
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Swag\PayPal\Util\Lifecycle\Method;

use Shopware\Core\Framework\Log\Package;
use Swag\PayPal\Checkout\Payment\Method\PayLaterHandler;
use Swag\PayPal\RestApi\V1\Api\MerchantIntegrations;
use Swag\PayPal\Storefront\Data\CheckoutDataMethodInterface;
use Swag\PayPal\Storefront\Data\Service\AbstractCheckoutDataService;
use Swag\PayPal\Storefront\Data\Service\PayLaterCheckoutDataService;
use Swag\PayPal\Util\Availability\AvailabilityContext;

/**
 * @internal
 */
#[Package('checkout')]
class PayLaterMethodData extends AbstractMethodData implements CheckoutDataMethodInterface
{
    public const TECHNICAL_NAME = 'swag_paypal_pay_later';

    public const PAYPAL_PAY_LATER_FIELD_DATA_EXTENSION_ID = 'payPalPayLaterFieldData';

    /**
     * @return array<string, array<string, string>>
     */
    public function getTranslations(): array
    {
        return [
            'de-DE' => [
                'description' => 'Jetzt kaufen und später bezahlen - unterstützt von Paypal',
                'name' => 'Später Bezahlen',
            ],
            'en-GB' => [
                'description' => 'Buy now and pay later - provided by Paypal',
                'name' => 'Pay Later',
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
        return PayLaterHandler::class;
    }

    public function getTechnicalName(): string
    {
        return self::TECHNICAL_NAME;
    }

    public function isAvailable(AvailabilityContext $availabilityContext): bool
    {
        return ($availabilityContext->getCurrencyCode() === 'EUR'
                && \in_array($availabilityContext->getBillingCountryCode(), ['DE', 'ES', 'FR', 'IT'], true))
            || ($availabilityContext->getCurrencyCode() === 'GBP'
                && $availabilityContext->getBillingCountryCode() === 'GB')
            || ($availabilityContext->getCurrencyCode() === 'AUD'
                && $availabilityContext->getBillingCountryCode() === 'AU')
            || ($availabilityContext->getCurrencyCode() === 'USD'
                && $availabilityContext->getBillingCountryCode() === 'US');
    }

    public function getInitialState(): bool
    {
        return true;
    }

    public function getMediaFileName(): ?string
    {
        return 'paypal';
    }

    public function getCheckoutDataService(): AbstractCheckoutDataService
    {
        return $this->container->get(PayLaterCheckoutDataService::class);
    }

    public function getCheckoutTemplateExtensionId(): string
    {
        return self::PAYPAL_PAY_LATER_FIELD_DATA_EXTENSION_ID;
    }

    public function validateCapability(MerchantIntegrations $merchantIntegrations): string
    {
        return self::CAPABILITY_ACTIVE;
    }
}
