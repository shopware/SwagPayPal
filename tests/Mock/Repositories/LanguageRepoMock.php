<?php declare(strict_types=1);
/**
 * (c) shopware AG <info@shopware.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Swag\PayPal\Test\Mock\Repositories;

use Shopware\Core\Defaults;
use Shopware\Core\Framework\Context;
use Shopware\Core\Framework\DataAbstractionLayer\EntityCollection;
use Shopware\Core\Framework\DataAbstractionLayer\EntityDefinition;
use Shopware\Core\Framework\DataAbstractionLayer\EntityRepositoryInterface;
use Shopware\Core\Framework\DataAbstractionLayer\Event\EntityWrittenContainerEvent;
use Shopware\Core\Framework\DataAbstractionLayer\Search\AggregatorResult;
use Shopware\Core\Framework\DataAbstractionLayer\Search\Criteria;
use Shopware\Core\Framework\DataAbstractionLayer\Search\EntitySearchResult;
use Shopware\Core\Framework\DataAbstractionLayer\Search\IdSearchResult;
use Shopware\Core\Framework\Language\LanguageDefinition;
use Shopware\Core\Framework\Language\LanguageEntity;
use Shopware\Core\System\Locale\LocaleEntity;

class LanguageRepoMock implements EntityRepositoryInterface
{
    public const LOCALE_CODE = 'en-GB';
    public const LANGUAGE_ID_WITHOUT_LOCALE = '3e5780c194c342d1b63d170400199f03';

    public function getDefinition(): EntityDefinition
    {
        return new LanguageDefinition();
    }

    public function aggregate(Criteria $criteria, Context $context): AggregatorResult
    {
    }

    public function searchIds(Criteria $criteria, Context $context): IdSearchResult
    {
    }

    public function search(Criteria $criteria, Context $context): EntitySearchResult
    {
        $withLocale = $criteria->getIds()[0] !== self::LANGUAGE_ID_WITHOUT_LOCALE;

        return new EntitySearchResult(
            1,
            new EntityCollection([$this->createLanguageEntity($withLocale)]),
            null,
            $criteria,
            $context
        );
    }

    public function update(array $data, Context $context): EntityWrittenContainerEvent
    {
    }

    public function upsert(array $data, Context $context): EntityWrittenContainerEvent
    {
    }

    public function create(array $data, Context $context): EntityWrittenContainerEvent
    {
    }

    public function delete(array $data, Context $context): EntityWrittenContainerEvent
    {
    }

    public function createVersion(string $id, Context $context, ?string $name = null, ?string $versionId = null): string
    {
    }

    public function merge(string $versionId, Context $context): void
    {
    }

    public function clone(string $id, Context $context, ?string $newId = null): EntityWrittenContainerEvent
    {
    }

    private function createLanguageEntity(bool $withLocale): LanguageEntity
    {
        $languageEntity = new LanguageEntity();

        if ($withLocale) {
            $languageEntity->setId(Defaults::LANGUAGE_SYSTEM);
            $locale = $this->createLocaleEntity();
            $languageEntity->setLocale($locale);
        } else {
            $languageEntity->setId(self::LANGUAGE_ID_WITHOUT_LOCALE);
        }

        return $languageEntity;
    }

    private function createLocaleEntity(): LocaleEntity
    {
        $locale = new LocaleEntity();
        $locale->setCode(self::LOCALE_CODE);

        return $locale;
    }
}
