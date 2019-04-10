<?php declare(strict_types=1);
/**
 * (c) shopware AG <info@shopware.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Swag\PayPal\Test\Mock\Repositories;

use Shopware\Core\Framework\Context;
use Shopware\Core\Framework\DataAbstractionLayer\EntityCollection;
use Shopware\Core\Framework\DataAbstractionLayer\EntityRepositoryInterface;
use Shopware\Core\Framework\DataAbstractionLayer\Event\EntityWrittenContainerEvent;
use Shopware\Core\Framework\DataAbstractionLayer\Search\AggregatorResult;
use Shopware\Core\Framework\DataAbstractionLayer\Search\Criteria;
use Shopware\Core\Framework\DataAbstractionLayer\Search\EntitySearchResult;
use Shopware\Core\Framework\DataAbstractionLayer\Search\IdSearchResult;
use Shopware\Core\Framework\Event\NestedEventCollection;
use Swag\PayPal\Setting\SwagPayPalSettingGeneralCollection;
use Swag\PayPal\Setting\SwagPayPalSettingGeneralEntity;
use Swag\PayPal\Test\Helper\ConstantsForTesting;
use Swag\PayPal\Test\Mock\Setting\Service\SettingsServiceMock;
use Swag\PayPal\Test\Setting\Service\SettingsServiceTest;

class SwagPayPalSettingGeneralRepoMock implements EntityRepositoryInterface
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
        if ($context->hasExtension(SettingsServiceTest::THROW_EXCEPTION)) {
            $entitySearchResult = $this->createEntitySearchResult($criteria, $context);
            $entitySearchResult->getEntities()->clear();

            return $entitySearchResult;
        }

        return $this->createEntitySearchResult($criteria, $context);
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

    public function clone(string $id, Context $context, ?string $newId = null): EntityWrittenContainerEvent
    {
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

    private function createSettingGeneral(): SwagPayPalSettingGeneralEntity
    {
        $settingGeneral = new SwagPayPalSettingGeneralEntity();
        $settingGeneral->setId(SettingsServiceMock::PAYPAL_SETTING_ID);
        $settingGeneral->setCreatedAt(new \DateTime());
        $settingGeneral->setUpdatedAt(new \DateTime());

        return $settingGeneral;
    }
}
