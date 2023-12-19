<?php declare(strict_types=1);
/*
 * (c) shopware AG <info@shopware.com>
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Swag\PayPal\Test\Mock\Repositories;

use Shopware\Core\Defaults;
use Shopware\Core\Framework\Context;
use Shopware\Core\Framework\DataAbstractionLayer\EntityDefinition;
use Shopware\Core\Framework\DataAbstractionLayer\Search\Criteria;
use Shopware\Core\Framework\DataAbstractionLayer\Search\EntitySearchResult;
use Shopware\Core\Framework\Log\Package;
use Shopware\Core\System\Language\LanguageCollection;
use Shopware\Core\System\Language\LanguageDefinition;
use Shopware\Core\System\Language\LanguageEntity;
use Shopware\Core\System\Locale\LocaleEntity;

/**
 * @extends AbstractRepoMock<LanguageCollection>
 *
 * @internal
 */
#[Package('checkout')]
class LanguageRepoMock extends AbstractRepoMock
{
    public const LOCALE_CODE = 'en-GB';

    public function getDefinition(): EntityDefinition
    {
        return new LanguageDefinition();
    }

    public function search(Criteria $criteria, Context $context): EntitySearchResult
    {
        return new EntitySearchResult(
            $this->getDefinition()->getEntityName(),
            1,
            new LanguageCollection([$this->createLanguageEntity()]),
            null,
            $criteria,
            $context
        );
    }

    private function createLanguageEntity(): LanguageEntity
    {
        $languageEntity = new LanguageEntity();
        $languageEntity->setId(Defaults::LANGUAGE_SYSTEM);
        $locale = new LocaleEntity();
        $locale->setCode(self::LOCALE_CODE);
        $languageEntity->setLocale($locale);

        return $languageEntity;
    }
}
