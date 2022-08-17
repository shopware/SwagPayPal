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
use Shopware\Core\System\Currency\Rule\CurrencyRule;
use Swag\PayPal\RestApi\V1\Api\MerchantIntegrations;
use Swag\PayPal\RestApi\V1\Api\MerchantIntegrations\Product;

/**
 * @internal not used yet, missing Storefront fields
 */
class BoletoBancarioMethodData extends AbstractMethodData
{
    private const AVAILABILITY_RULE_NAME = 'PayPalBlikBoletoBancarioAvailabilityRule';

    public function getTranslations(): array
    {
        return [
            'de-DE' => [
                'description' => '',
                'name' => 'Boleto Bancário',
            ],
            'en-GB' => [
                'description' => '',
                'name' => 'Boleto Bancário',
            ],
        ];
    }

    public function getPosition(): int
    {
        return -95;
    }

    public function getHandler(): string
    {
        return 'Swag\PayPal\Checkout\Payment\Method\BoletoBancarioAPMHandler';
    }

    public function getRuleData(Context $context): ?array
    {
        return [
            'name' => self::AVAILABILITY_RULE_NAME,
            'priority' => 1,
            'description' => 'Determines whether or not the PayPal - Boleto Bancário payment method is available for the given rule context.',
            'conditions' => [
                [
                    'type' => (new AndRule())->getName(),
                    'children' => [
                        [
                            'type' => (new BillingCountryRule())->getName(),
                            'value' => [
                                'operator' => BillingCountryRule::OPERATOR_EQ,
                                'countryIds' => $this->getCountryIds(['BR'], $context),
                            ],
                        ],
                        [
                            'type' => (new CurrencyRule())->getName(),
                            'value' => [
                                'operator' => CurrencyRule::OPERATOR_EQ,
                                'currencyIds' => $this->getCurrencyIds(['BRL'], $context),
                            ],
                        ],
                        [
                            'type' => (new CartAmountRule())->getName(),
                            'value' => [
                                'operator' => CartAmountRule::OPERATOR_LTE,
                                'amount' => 35000.0,
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
        return 'apm_boletobancario';
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
