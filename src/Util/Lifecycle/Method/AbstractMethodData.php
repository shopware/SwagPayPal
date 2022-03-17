<?php declare(strict_types=1);
/*
 * (c) shopware AG <info@shopware.com>
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Swag\PayPal\Util\Lifecycle\Method;

use Shopware\Core\Framework\Context;
use Shopware\Core\Framework\DataAbstractionLayer\EntityRepositoryInterface;
use Shopware\Core\Framework\DataAbstractionLayer\Search\Criteria;
use Shopware\Core\Framework\DataAbstractionLayer\Search\Filter\EqualsAnyFilter;
use Shopware\Core\Framework\Uuid\Uuid;
use Shopware\Core\System\Country\CountryDefinition;
use Shopware\Core\System\Currency\CurrencyDefinition;
use Swag\PayPal\RestApi\V1\Api\MerchantIntegrations;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * @psalm-consistent-constructor
 */
abstract class AbstractMethodData
{
    public const CAPABILITY_ACTIVE = 'active';
    public const CAPABILITY_INACTIVE = 'inactive';
    public const CAPABILITY_LIMITED = 'limited';

    protected ContainerInterface $container;

    /**
     * @psalm-suppress ContainerDependency
     */
    final public function __construct(ContainerInterface $container)
    {
        $this->container = $container;
    }

    abstract public function getTranslations(): array;

    abstract public function getPosition(): int;

    abstract public function getHandler(): string;

    abstract public function getRuleData(Context $context): ?array;

    abstract public function getInitialState(): bool;

    abstract public function validateCapability(MerchantIntegrations $merchantIntegrations): string;

    abstract public function getMediaFileName(): ?string;

    protected function getCountryIds(array $countryIsos, Context $context): array
    {
        $criteria = new Criteria();
        $criteria->addFilter(new EqualsAnyFilter('iso', $countryIsos));

        /** @var EntityRepositoryInterface $countryRepository */
        $countryRepository = $this->container->get(\sprintf('%s.repository', CountryDefinition::ENTITY_NAME));

        /** @var string[] $countryIds */
        $countryIds = $countryRepository->searchIds($criteria, $context)->getIds();

        if (empty($countryIds)) {
            // if country does not exist, enter invalid uuid so rule always fails. empty is not allowed
            return [Uuid::randomHex()];
        }

        return $countryIds;
    }

    protected function getCurrencyIds(array $currencyCodes, Context $context): array
    {
        $criteria = new Criteria();
        $criteria->addFilter(new EqualsAnyFilter('isoCode', $currencyCodes));

        /** @var EntityRepositoryInterface $currencyRepository */
        $currencyRepository = $this->container->get(\sprintf('%s.repository', CurrencyDefinition::ENTITY_NAME));

        /** @var string[] $currencyIds */
        $currencyIds = $currencyRepository->searchIds($criteria, $context)->getIds();

        if (empty($currencyIds)) {
            // if currency does not exist, enter invalid uuid so rule always fails. empty is not allowed
            return [Uuid::randomHex()];
        }

        return $currencyIds;
    }
}
