<?php declare(strict_types=1);
/*
 * (c) shopware AG <info@shopware.com>
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Swag\PayPal\Util\Lifecycle\Method;

use Shopware\Core\Framework\Log\Package;
use Shopware\PayPalSDK\Struct\V1\MerchantIntegrations;
use Swag\PayPal\Checkout\Payment\Method\PayLaterHandler;
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
     * @var array<string, array{currency: string, minAmount: float, maxAmount: float}>
     *
     * @see https://developer.paypal.com/studio/checkout/pay-later/{{countryCode}}
     */
    public const PAYPAL_PAY_LATER_CRITERIA = [
        'DE' => ['currency' => 'EUR', 'minAmount' => 1.00, 'maxAmount' => 5000.00],
        'FR' => ['currency' => 'EUR', 'minAmount' => 30.00, 'maxAmount' => 2000.00],
        'IT' => ['currency' => 'EUR', 'minAmount' => 30.00, 'maxAmount' => 2000.00],
        'ES' => ['currency' => 'EUR', 'minAmount' => 30.00, 'maxAmount' => 2000.00],
        'GB' => ['currency' => 'GBP', 'minAmount' => 30.00, 'maxAmount' => 2000.00],
        'US' => ['currency' => 'USD', 'minAmount' => 30.00, 'maxAmount' => 10000.00],
        'AU' => ['currency' => 'AUD', 'minAmount' => 30.00, 'maxAmount' => 2000.00],
    ];

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
        $countryCode = $availabilityContext->getBillingCountryCode();
        $currencyCode = $availabilityContext->getCurrencyCode();
        $totalAmount = $availabilityContext->getTotalAmount();

        if (!isset(self::PAYPAL_PAY_LATER_CRITERIA[$countryCode])) {
            return false;
        }

        $criteria = self::PAYPAL_PAY_LATER_CRITERIA[$countryCode];

        return $currencyCode === $criteria['currency']
            && $totalAmount >= $criteria['minAmount']
            && $totalAmount <= $criteria['maxAmount'];
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
