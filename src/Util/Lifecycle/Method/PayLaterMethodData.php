<?php declare(strict_types=1);
/*
 * (c) shopware AG <info@shopware.com>
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Swag\PayPal\Util\Lifecycle\Method;

use Shopware\Core\Checkout\Customer\Rule\BillingCountryRule;
use Shopware\Core\Framework\Context;
use Shopware\Core\Framework\Rule\Container\AndRule;
use Shopware\Core\Framework\Rule\Container\OrRule;
use Shopware\Core\System\Currency\Rule\CurrencyRule;
use Swag\PayPal\Checkout\Payment\Method\PayLaterHandler;
use Swag\PayPal\RestApi\V1\Api\MerchantIntegrations;
use Swag\PayPal\Storefront\Data\CheckoutDataMethodInterface;
use Swag\PayPal\Storefront\Data\Service\AbstractCheckoutDataService;
use Swag\PayPal\Storefront\Data\Service\PayLaterCheckoutDataService;

class PayLaterMethodData extends AbstractMethodData implements CheckoutDataMethodInterface
{
    public const PAYPAL_PAY_LATER_FIELD_DATA_EXTENSION_ID = 'payPalPayLaterFieldData';
    private const AVAILABILITY_RULE_NAME = 'PayPalPayLaterAvailabilityRule';

    public function getTranslations(): array
    {
        return [
            'de-DE' => [
                'description' => 'Jetzt kaufen und später bezahlen - unterstützt von Paypal',
                'name' => 'Später bezahlen',
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

    public function getRuleData(Context $context): ?array
    {
        return [
            'name' => self::AVAILABILITY_RULE_NAME,
            'priority' => 1,
            'description' => 'Determines whether or not the PayPal - Pay Later payment method is available for the given rule context.',
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
                                        'countryIds' => $this->getCountryIds(['US'], $context),
                                    ],
                                ],
                                [
                                    'type' => (new CurrencyRule())->getName(),
                                    'value' => [
                                        'operator' => CurrencyRule::OPERATOR_EQ,
                                        'currencyIds' => $this->getCurrencyIds(['USD'], $context),
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
                                        'countryIds' => $this->getCountryIds(['AU'], $context),
                                    ],
                                ],
                                [
                                    'type' => (new CurrencyRule())->getName(),
                                    'value' => [
                                        'operator' => CurrencyRule::OPERATOR_EQ,
                                        'currencyIds' => $this->getCurrencyIds(['AUD'], $context),
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
                                        'countryIds' => $this->getCountryIds(['DE', 'ES', 'FR', 'IT'], $context),
                                    ],
                                ],
                                [
                                    'type' => (new CurrencyRule())->getName(),
                                    'value' => [
                                        'operator' => CurrencyRule::OPERATOR_EQ,
                                        'currencyIds' => $this->getCurrencyIds(['EUR'], $context),
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
                                        'countryIds' => $this->getCountryIds(['UK'], $context),
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
