<?php declare(strict_types=1);
/**
 * (c) shopware AG <info@shopware.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace SwagPayPal\Test\Mock\Repositories;

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
use SwagPayPal\Setting\SwagPayPalSettingGeneralStruct;
use SwagPayPal\Test\Helper\ConstantsForTesting;

class SwagPayPalSettingGeneralRepoMock implements RepositoryInterface
{
    public const PAYPAL_SETTING_ID = 'testSettingsId';

    public const PAYPAL_SETTING_WITHOUT_TOKEN = 'settingsWithoutToken';

    public const PAYPAL_SETTING_WITHOUT_TOKEN_AND_ID = 'settingsWithoutTokenAndId';

    public const ALREADY_EXISTING_WEBHOOK_ID = 'alreadyExistingTestWebhookId';

    public const ALREADY_EXISTING_WEBHOOK_EXECUTE_TOKEN = 'testWebhookExecuteToken';

    private $data = [];

    public function aggregate(Criteria $criteria, Context $context): AggregatorResult
    {
    }

    public function searchIds(Criteria $criteria, Context $context): IdSearchResult
    {
    }

    public function search(Criteria $criteria, Context $context): EntitySearchResult
    {
        if ($context->hasExtension(self::PAYPAL_SETTING_WITHOUT_TOKEN)) {
            return $this->createEntitySearchResultWithoutToken($criteria, $context);
        }

        if ($context->hasExtension(self::PAYPAL_SETTING_WITHOUT_TOKEN_AND_ID)) {
            return $this->createEntitySearchResultWithoutTokenAndId($criteria, $context);
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
            $this->createEntityCollectionWithTokenAndId(),
            null,
            $criteria,
            $context
        );
    }

    private function createEntityCollectionWithTokenAndId(): EntityCollection
    {
        $settingGeneral = $this->createSettingGeneral();
        $settingGeneral->setWebhookExecuteToken(self::ALREADY_EXISTING_WEBHOOK_EXECUTE_TOKEN);
        $settingGeneral->setWebhookId(self::ALREADY_EXISTING_WEBHOOK_ID);

        return new EntityCollection([$settingGeneral]);
    }

    private function createSettingGeneral(): SwagPayPalSettingGeneralStruct
    {
        $settingGeneral = new SwagPayPalSettingGeneralStruct();
        $settingGeneral->setId(self::PAYPAL_SETTING_ID);

        return $settingGeneral;
    }

    private function createEntitySearchResultWithoutToken(Criteria $criteria, Context $context): EntitySearchResult
    {
        return new EntitySearchResult(
            ConstantsForTesting::REPO_SEARCH_RESULT_TOTAL_WITH_RESULTS,
            $this->createEntityCollectionWithoutToken(),
            null,
            $criteria,
            $context
        );
    }

    private function createEntityCollectionWithoutToken(): EntityCollection
    {
        $settingGeneral = $this->createSettingGeneral();
        $settingGeneral->setWebhookId(self::ALREADY_EXISTING_WEBHOOK_ID);

        return new EntityCollection([$settingGeneral]);
    }

    private function createEntitySearchResultWithoutTokenAndId(Criteria $criteria, Context $context): EntitySearchResult
    {
        return new EntitySearchResult(
            ConstantsForTesting::REPO_SEARCH_RESULT_TOTAL_WITH_RESULTS,
            $this->createEntityCollectionWithoutTokenAndId(),
            null,
            $criteria,
            $context
        );
    }

    private function createEntityCollectionWithoutTokenAndId(): EntityCollection
    {
        return new EntityCollection([$this->createSettingGeneral()]);
    }
}
