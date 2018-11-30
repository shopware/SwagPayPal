<?php declare(strict_types=1);
/**
 * (c) shopware AG <info@shopware.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace SwagPayPal\Test\Mock\Repositories;

use DateTime;
use Shopware\Core\Framework\Context;
use Shopware\Core\Framework\DataAbstractionLayer\EntityCollection;
use Shopware\Core\Framework\DataAbstractionLayer\Event\EntityWrittenContainerEvent;
use Shopware\Core\Framework\DataAbstractionLayer\Read\ReadCriteria;
use Shopware\Core\Framework\DataAbstractionLayer\RepositoryInterface;
use Shopware\Core\Framework\DataAbstractionLayer\Search\AggregatorResult;
use Shopware\Core\Framework\DataAbstractionLayer\Search\Criteria;
use Shopware\Core\Framework\DataAbstractionLayer\Search\EntitySearchResult;
use Shopware\Core\Framework\DataAbstractionLayer\Search\IdSearchResult;
use Shopware\Core\Framework\Event\NestedEventCollection;
use SwagPayPal\Setting\SwagPayPalSettingGeneralCollection;
use SwagPayPal\Setting\SwagPayPalSettingGeneralStruct;
use SwagPayPal\Test\Helper\ConstantsForTesting;
use SwagPayPal\Test\Mock\Setting\Service\SettingsProviderMock;
use SwagPayPal\Test\Setting\Service\SettingsProviderTest;

class SwagPayPalSettingGeneralRepoMock implements RepositoryInterface
{
    private $data = [];

    public function aggregate(Criteria $criteria, Context $context): AggregatorResult
    {
    }

    public function searchIds(Criteria $criteria, Context $context): IdSearchResult
    {
    }

    public function search(Criteria $criteria, Context $context): EntitySearchResult
    {
        if ($context->hasExtension(SettingsProviderTest::THROW_EXCEPTION)) {
            $entitySearchResult = $this->createEntitySearchResult($criteria, $context);
            $entitySearchResult->getEntities()->clear();

            return $entitySearchResult;
        }

        return $this->createEntitySearchResult($criteria, $context);
    }

    public function read(ReadCriteria $criteria, Context $context): EntityCollection
    {
    }

    public function update(array $data, Context $context): EntityWrittenContainerEvent
    {
        $this->data = $data;

        return new EntityWrittenContainerEvent($context, new NestedEventCollection([]), []);
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

    public function getData(): array
    {
        return $this->data[0];
    }

    private function createEntitySearchResult(Criteria $criteria, Context $context): EntitySearchResult
    {
        return new EntitySearchResult(
            ConstantsForTesting::REPO_SEARCH_RESULT_TOTAL_WITH_RESULTS,
            $this->createEntityCollection(),
            null,
            $criteria,
            $context
        );
    }

    private function createEntityCollection(): EntityCollection
    {
        $settingGeneral = $this->createSettingGeneral();

        return new SwagPayPalSettingGeneralCollection([$settingGeneral]);
    }

    private function createSettingGeneral(): SwagPayPalSettingGeneralStruct
    {
        $settingGeneral = new SwagPayPalSettingGeneralStruct();
        $settingGeneral->setId(SettingsProviderMock::PAYPAL_SETTING_ID);
        $settingGeneral->setCreatedAt(new DateTime());
        $settingGeneral->setUpdatedAt(new DateTime());

        return $settingGeneral;
    }
}
