<?php declare(strict_types=1);
/*
 * (c) shopware AG <info@shopware.com>
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Swag\PayPal\Util\Lifecycle\Method;

use Shopware\Core\Checkout\Cart\Rule\CartAmountRule;
use Shopware\Core\Checkout\Customer\Rule\BillingCountryRule;
use Shopware\Core\Framework\Context;
use Shopware\Core\Framework\Rule\Container\AndRule;
use Shopware\Core\Framework\Rule\Container\OrRule;
use Shopware\Core\System\Currency\Rule\CurrencyRule;
use Swag\PayPal\RestApi\V1\Api\MerchantIntegrations;
use Swag\PayPal\RestApi\V1\Api\MerchantIntegrations\Capability;

class SofortMethodData extends AbstractMethodData
{
    private const AVAILABILITY_RULE_NAME = 'PayPalSofortAPMAvailabilityRule';

    public function getTranslations(): array
    {
        return [
            'de-DE' => [
                'description' => '',
                'name' => 'Sofort',
            ],
            'en-GB' => [
                'description' => '',
                'name' => 'Sofort',
            ],
        ];
    }

    public function getPosition(): int
    {
        return -87;
    }

    public function getHandler(): string
    {
        return 'Swag\PayPal\Checkout\Payment\Method\SofortAPMHandler';
    }

    public function getRuleData(Context $context): ?array
    {
        return [
            'name' => self::AVAILABILITY_RULE_NAME,
            'priority' => 1,
            'description' => 'Determines whether or not the PayPal - Sofort payment method is available for the given rule context.',
            'conditions' => [
                [
                    'type' => (new OrRule())->getName(),
                    'children' => [
                        [
                            'type' => (new AndRule())->getName(),
                            'children' => [
                                [
                                    'type' => (new BillingCountryRule())->getName(),
                                    'value' => [
                                        'operator' => BillingCountryRule::OPERATOR_EQ,
                                        'countryIds' => $this->getCountryIds(['AT', 'BE', 'DE', 'ES', 'IT', 'NL'], $context),
                                    ],
                                ],
                                [
                                    'type' => (new CurrencyRule())->getName(),
                                    'value' => [
                                        'operator' => CurrencyRule::OPERATOR_EQ,
                                        'currencyIds' => $this->getCurrencyIds(['EUR'], $context),
                                    ],
                                ],
                                [
                                    'type' => (new CartAmountRule())->getName(),
                                    'value' => [
                                        'operator' => CartAmountRule::OPERATOR_GTE,
                                        'amount' => 1.0,
                                    ],
                                ],
                            ],
                        ],
                        [
                            'type' => (new AndRule())->getName(),
                            'children' => [
                                [
                                    'type' => (new BillingCountryRule())->getName(),
                                    'value' => [
                                        'operator' => BillingCountryRule::OPERATOR_EQ,
                                        'countryIds' => $this->getCountryIds(['GB'], $context),
                                    ],
                                ],
                                [
                                    'type' => (new CurrencyRule())->getName(),
                                    'value' => [
                                        'operator' => CurrencyRule::OPERATOR_EQ,
                                        'currencyIds' => $this->getCurrencyIds(['GBP'], $context),
                                    ],
                                ],
                            ],
                        ],
                    ],
                ],
            ],
        ];
    }

    public function getInitialState(): bool
    {
        return false;
    }

    public function getMediaFileName(): ?string
    {
        return 'apm_sofort';
    }

    public function validateCapability(MerchantIntegrations $merchantIntegrations): string
    {
        $capability = $merchantIntegrations->getSpecificCapability('ALT_PAY_PROCESSING');
        if ($capability !== null && $capability->getStatus() === Capability::STATUS_ACTIVE) {
            return self::CAPABILITY_ACTIVE;
        }

        return self::CAPABILITY_INELIGIBLE;
    }
}
