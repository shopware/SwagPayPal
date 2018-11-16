<?php declare(strict_types=1);
/**
 * (c) shopware AG <info@shopware.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace SwagPayPal\Test\Mock\Repositories;

use Shopware\Core\Defaults;
use Shopware\Core\Framework\Context;
use Shopware\Core\Framework\DataAbstractionLayer\EntityCollection;
use Shopware\Core\Framework\DataAbstractionLayer\Event\EntityWrittenContainerEvent;
use Shopware\Core\Framework\DataAbstractionLayer\Read\ReadCriteria;
use Shopware\Core\Framework\DataAbstractionLayer\RepositoryInterface;
use Shopware\Core\Framework\DataAbstractionLayer\Search\AggregatorResult;
use Shopware\Core\Framework\DataAbstractionLayer\Search\Criteria;
use Shopware\Core\Framework\DataAbstractionLayer\Search\EntitySearchResult;
use Shopware\Core\Framework\DataAbstractionLayer\Search\IdSearchResult;
use Shopware\Core\System\Language\LanguageStruct;
use Shopware\Core\System\Locale\LocaleStruct;

class LanguageRepositoryMock implements RepositoryInterface
{
    public const LOCALE_CODE = 'en_GB';

    public function aggregate(Criteria $criteria, Context $context): AggregatorResult
    {
    }

    public function searchIds(Criteria $criteria, Context $context): IdSearchResult
    {
    }

    public function search(Criteria $criteria, Context $context): EntitySearchResult
    {
    }

    public function read(ReadCriteria $criteria, Context $context): EntityCollection
    {
        return new EntityCollection([$this->createLanguageStruct()]);
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

    private function createLanguageStruct(): LanguageStruct
    {
        $languageStruct = new LanguageStruct();
        $languageStruct->setId(Defaults::LANGUAGE_EN);
        $locale = $this->createLocaleStruct();
        $languageStruct->setLocale($locale);

        return $languageStruct;
    }

    private function createLocaleStruct(): LocaleStruct
    {
        $locale = new LocaleStruct();
        $locale->setCode(self::LOCALE_CODE);

        return $locale;
    }
}
