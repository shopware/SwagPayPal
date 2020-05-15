<?php declare(strict_types=1);
/*
 * (c) shopware AG <info@shopware.com>
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Swag\PayPal\Test\Mock\Repositories;

use Shopware\Core\Framework\Context;
use Shopware\Core\Framework\DataAbstractionLayer\EntityCollection;
use Shopware\Core\Framework\DataAbstractionLayer\EntityDefinition;
use Shopware\Core\Framework\DataAbstractionLayer\EntityRepositoryInterface;
use Shopware\Core\Framework\DataAbstractionLayer\Event\EntityWrittenContainerEvent;
use Shopware\Core\Framework\DataAbstractionLayer\Search\AggregationResult\AggregationResultCollection;
use Shopware\Core\Framework\DataAbstractionLayer\Search\Criteria;
use Shopware\Core\Framework\DataAbstractionLayer\Search\EntitySearchResult;
use Shopware\Core\Framework\DataAbstractionLayer\Search\Filter\EqualsFilter;
use Shopware\Core\Framework\DataAbstractionLayer\Search\IdSearchResult;
use Shopware\Core\Framework\Uuid\Uuid;
use Shopware\Core\System\SystemConfig\SystemConfigDefinition;
use Shopware\Core\System\SystemConfig\SystemConfigEntity;
use Swag\PayPal\Setting\Service\SettingsService;
use Swag\PayPal\Test\Webhook\WebhookControllerTest;
use Swag\PayPal\Test\Webhook\WebhookServiceTest;
use Swag\PayPal\Webhook\WebhookService;

class SystemConfigRepoMock implements EntityRepositoryInterface
{
    public function getDefinition(): EntityDefinition
    {
        return new SystemConfigDefinition();
    }

    public function aggregate(Criteria $criteria, Context $context): AggregationResultCollection
    {
    }

    public function searchIds(Criteria $criteria, Context $context): IdSearchResult
    {
    }

    public function clone(string $id, Context $context, ?string $newId = null): EntityWrittenContainerEvent
    {
    }

    public function search(Criteria $criteria, Context $context): EntitySearchResult
    {
        /** @var EqualsFilter $filter */
        $filter = $criteria->getFilters()[0];
        if ($context->hasExtension(WebhookControllerTest::EMPTY_TOKEN)
            || $filter->getValue() !== WebhookServiceTest::ALREADY_EXISTING_WEBHOOK_EXECUTE_TOKEN
        ) {
            return new EntitySearchResult(
                0,
                new EntityCollection([]),
                null,
                $criteria,
                $context
            );
        }

        return new EntitySearchResult(
            1,
            new EntityCollection([
                $this->createConfigEntity(),
            ]),
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

    private function createConfigEntity(): SystemConfigEntity
    {
        $systemConfigEntity = new SystemConfigEntity();
        $systemConfigEntity->setId(Uuid::randomHex());
        $systemConfigEntity->setConfigurationKey(SettingsService::SYSTEM_CONFIG_DOMAIN . WebhookService::WEBHOOK_TOKEN_CONFIG_KEY);
        $systemConfigEntity->setConfigurationValue(WebhookServiceTest::ALREADY_EXISTING_WEBHOOK_EXECUTE_TOKEN);

        return $systemConfigEntity;
    }
}
