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
use Swag\PayPal\Checkout\Payment\Method\SEPAHandler;
use Swag\PayPal\RestApi\V1\Api\MerchantIntegrations;
use Swag\PayPal\RestApi\V1\Api\MerchantIntegrations\Capability;
use Swag\PayPal\Storefront\Data\CheckoutDataMethodInterface;
use Swag\PayPal\Storefront\Data\Service\AbstractCheckoutDataService;
use Swag\PayPal\Storefront\Data\Service\SEPACheckoutDataService;

class SEPAMethodData extends AbstractMethodData implements CheckoutDataMethodInterface
{
    public const PAYPAL_SEPA_FIELD_DATA_EXTENSION_ID = 'payPalSEPAFieldData';
    private const AVAILABILITY_RULE_NAME = 'PayPalSEPAAvailabilityRule';

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

    public function getRuleData(Context $context): ?array
    {
        return [
            'name' => self::AVAILABILITY_RULE_NAME,
            'priority' => 1,
            'description' => 'Determines whether or not the PayPal - SEPA payment method is available for the given rule context.',
            'conditions' => [
                [
                    'type' => (new AndRule())->getName(),
                    'children' => [
                        [
                            'type' => (new BillingCountryRule())->getName(),
                            'value' => [
                                'operator' => BillingCountryRule::OPERATOR_EQ,
                                'countryIds' => $this->getCountryIds(['DE'], $context),
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
            ],
        ];
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
        $capability = $merchantIntegrations->getSpecificCapability('ALT_PAY_PROCESSING');
        if ($capability !== null && $capability->getStatus() === Capability::STATUS_ACTIVE) {
            return self::CAPABILITY_ACTIVE;
        }

        return self::CAPABILITY_INACTIVE;
    }
}
