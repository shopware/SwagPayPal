<?php declare(strict_types=1);
/*
 * (c) shopware AG <info@shopware.com>
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Swag\PayPal\Util\Lifecycle\Method;

use Shopware\Core\Framework\Context;
use Swag\PayPal\Checkout\Payment\PayPalPaymentHandler;
use Swag\PayPal\RestApi\V1\Api\MerchantIntegrations;

class PayPalMethodData extends AbstractMethodData
{
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

    public function getRuleData(Context $context): ?array
    {
        return null;
    }

    public function getInitialState(): bool
    {
        return true;
    }

    public function validateCapability(MerchantIntegrations $merchantIntegrations): string
    {
        return self::CAPABILITY_ACTIVE;
    }
}
