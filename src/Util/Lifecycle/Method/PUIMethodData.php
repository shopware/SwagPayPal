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
use Shopware\Core\Framework\DataAbstractionLayer\EntityRepositoryInterface;
use Shopware\Core\Framework\DataAbstractionLayer\Search\Criteria;
use Shopware\Core\Framework\DataAbstractionLayer\Search\Filter\EqualsFilter;
use Shopware\Core\Framework\Rule\Container\AndRule;
use Shopware\Core\System\Country\CountryDefinition;
use Shopware\Core\System\Country\Exception\CountryNotFoundException;
use Shopware\Core\System\Currency\CurrencyDefinition;
use Shopware\Core\System\Currency\Rule\CurrencyRule;
use Swag\PayPal\Checkout\Exception\CurrencyNotFoundException;
use Swag\PayPal\Checkout\Payment\Method\PUIHandler;

class PUIMethodData extends AbstractMethodData
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
        return PUIHandler::class;
    }

    public function getRuleData(Context $context): ?array
    {
        $germanCountryId = $this->getGermanCountryId($context);
        $euroCurrencyId = $this->getEuroCurrencyId($context);

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
                            'type' => (new CurrencyRule())->getName(),
                            'value' => [
                                'operator' => CurrencyRule::OPERATOR_EQ,
                                'currencyIds' => [
                                    $euroCurrencyId,
                                ],
                            ],
                        ],
                        [
                            'type' => (new CartAmountRule())->getName(),
                            'value' => [
                                'operator' => CartAmountRule::OPERATOR_GTE,
                                'amount' => 5.0,
                            ],
                        ],
                        [
                            'type' => (new CartAmountRule())->getName(),
                            'value' => [
                                'operator' => CartAmountRule::OPERATOR_LTE,
                                'amount' => 2500.0,
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
        $germanCountryId = $countryRepository->searchIds($criteria, $context)->firstId();

        if ($germanCountryId === null) {
            throw new CountryNotFoundException($germanIso3);
        }

        return $germanCountryId;
    }

    /**
     * @throws CountryNotFoundException
     */
    private function getEuroCurrencyId(Context $context): string
    {
        $isoCode = 'EUR';
        $criteria = new Criteria();
        $criteria->addFilter(
            new EqualsFilter('isoCode', $isoCode)
        );

        /** @var EntityRepositoryInterface $currencyRepository */
        $currencyRepository = $this->container->get(\sprintf('%s.repository', CurrencyDefinition::ENTITY_NAME));
        $euroCurrencyId = $currencyRepository->searchIds($criteria, $context)->firstId();

        if ($euroCurrencyId === null) {
            throw new CurrencyNotFoundException($isoCode);
        }

        return $euroCurrencyId;
    }
}
