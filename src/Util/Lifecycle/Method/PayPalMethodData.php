<?php declare(strict_types=1);
/*
 * (c) shopware AG <info@shopware.com>
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Swag\PayPal\Util\Lifecycle\Method;

use Shopware\Core\Framework\Log\Package;
use Shopware\Core\System\SalesChannel\SalesChannelContext;
use Shopware\Core\System\SystemConfig\SystemConfigService;
use Swag\PayPal\Checkout\Payment\PayPalPaymentHandler;
use Swag\PayPal\RestApi\V1\Api\MerchantIntegrations;
use Swag\PayPal\Setting\Settings;
use Swag\PayPal\Storefront\Data\CheckoutDataMethodInterface;
use Swag\PayPal\Storefront\Data\Service\AbstractCheckoutDataService;
use Swag\PayPal\Storefront\Data\Service\SPBCheckoutDataService;
use Swag\PayPal\Util\Availability\AvailabilityContext;

/**
 * @internal
 */
#[Package('checkout')]
class PayPalMethodData extends AbstractMethodData implements CheckoutDataMethodInterface
{
    public const TECHNICAL_NAME = 'swag_paypal_paypal';

    public const PAYPAL_SMART_PAYMENT_BUTTONS_DATA_EXTENSION_ID = 'payPalSpbButtonData';

    /**
     * @return array<string, array<string, string>>
     */
    public function getTranslations(): array
    {
        return [
            'de-DE' => [
                'description' => 'Bezahlung per PayPal - einfach, schnell und sicher.',
                'name' => 'PayPal',
            ],
            'en-GB' => [
                'description' => 'Payment via PayPal - easy, fast and secure.',
                'name' => 'PayPal',
            ],
        ];
    }

    public function getPosition(): int
    {
        return -100;
    }

    public function getHandler(): string
    {
        return PayPalPaymentHandler::class;
    }

    public function getTechnicalName(): string
    {
        return self::TECHNICAL_NAME;
    }

    public function isAvailable(AvailabilityContext $availabilityContext): bool
    {
        if ($availabilityContext->isSubscription()) {
            $systemConfigService = $this->container->get(SystemConfigService::class);

            return $systemConfigService->getBool(Settings::VAULTING_ENABLED_WALLET, $availabilityContext->getSalesChannelId());
        }

        return true;
    }

    public function getInitialState(): bool
    {
        return true;
    }

    public function getMediaFileName(): ?string
    {
        return 'paypal';
    }

    public function validateCapability(MerchantIntegrations $merchantIntegrations): string
    {
        return self::CAPABILITY_ACTIVE;
    }

    public function getCheckoutDataService(): AbstractCheckoutDataService
    {
        return $this->container->get(SPBCheckoutDataService::class);
    }

    public function getCheckoutTemplateExtensionId(): string
    {
        return self::PAYPAL_SMART_PAYMENT_BUTTONS_DATA_EXTENSION_ID;
    }

    public function isVaultable(SalesChannelContext $context): bool
    {
        $systemConfigService = $this->container->get(SystemConfigService::class);

        return $systemConfigService->getBool(Settings::VAULTING_ENABLED_WALLET, $context->getSalesChannelId());
    }
}
