<?php declare(strict_types=1);
/*
 * (c) shopware AG <info@shopware.com>
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Swag\PayPal\Util\Lifecycle\Method;

use Shopware\Core\Checkout\Cart\Rule\CartAmountRule;
use Shopware\Core\Checkout\Customer\Rule\BillingCountryRule;
use Shopware\Core\Checkout\Customer\Rule\IsCompanyRule;
use Shopware\Core\Framework\Context;
use Shopware\Core\Framework\DataAbstractionLayer\EntityRepositoryInterface;
use Shopware\Core\Framework\DataAbstractionLayer\Search\Criteria;
use Shopware\Core\Framework\DataAbstractionLayer\Search\Filter\EqualsFilter;
use Shopware\Core\Framework\Rule\Container\AndRule;
use Shopware\Core\System\Country\CountryDefinition;
use Shopware\Core\System\Country\CountryEntity;
use Shopware\Core\System\Country\Exception\CountryNotFoundException;
use Swag\PayPal\Checkout\Payment\PayPalPuiPaymentHandler;

/**
 * @internal will be removed in a future release
 */
class PayPalPuiMethodData extends AbstractMethodData
{
    private const PAYPAL_PUI_AVAILABILITY_RULE_NAME = 'PayPalPuiAvailabilityRule';

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
        return PayPalPuiPaymentHandler::class;
    }

    public function getRuleData(Context $context): ?array
    {
        $germanCountryId = $this->getGermanCountryId($context);

        return [
            'name' => self::PAYPAL_PUI_AVAILABILITY_RULE_NAME,
            'priority' => 1,
            'description' => 'Determines whether or not the PayPal - Pay upon invoice payment method is available for the given rule context.',
            'conditions' => [
                [
                    'type' => (new AndRule())->getName(),
                    'children' => [
                        [
                            'type' => (new BillingCountryRule())->getName(),
                            'value' => [
                                'operator' => BillingCountryRule::OPERATOR_EQ,
                                'countryIds' => [
                                    $germanCountryId,
                                ],
                            ],
                        ],
                        [
                            'type' => (new IsCompanyRule())->getName(),
                            'value' => [
                                'isCompany' => false,
                            ],
                        ],
                        [
                            'type' => (new CartAmountRule())->getName(),
                            'value' => [
                                'operator' => CartAmountRule::OPERATOR_GTE,
                                'amount' => 2.0,
                            ],
                        ],
                        [
                            'type' => (new CartAmountRule())->getName(),
                            'value' => [
                                'operator' => CartAmountRule::OPERATOR_LTE,
                                'amount' => 1470.0,
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

    /**
     * @throws CountryNotFoundException
     */
    private function getGermanCountryId(Context $context): string
    {
        $germanIso3 = 'DEU';
        $criteria = new Criteria();
        $criteria->addFilter(
            new EqualsFilter('iso3', $germanIso3)
        );

        /** @var EntityRepositoryInterface $countryRepository */
        $countryRepository = $this->container->get(\sprintf('%s.repository', CountryDefinition::ENTITY_NAME));

        /** @var CountryEntity|null $germanCountry */
        $germanCountry = $countryRepository->search($criteria, $context)->first();

        if ($germanCountry === null) {
            throw new CountryNotFoundException($germanIso3);
        }

        return $germanCountry->getId();
    }
}
