<?php declare(strict_types=1);
/*
 * (c) shopware AG <info@shopware.com>
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Swag\PayPal\Test\Mock\Repositories;

use Shopware\Core\Framework\Context;
use Shopware\Core\Framework\DataAbstractionLayer\EntityDefinition;
use Shopware\Core\Framework\DataAbstractionLayer\Search\Criteria;
use Shopware\Core\Framework\DataAbstractionLayer\Search\EntitySearchResult;
use Shopware\Core\Framework\DataAbstractionLayer\Search\Filter\EqualsFilter;
use Shopware\Core\Framework\Log\Package;
use Shopware\Core\Framework\Uuid\Uuid;
use Shopware\Core\System\SystemConfig\SystemConfigCollection;
use Shopware\Core\System\SystemConfig\SystemConfigDefinition;
use Shopware\Core\System\SystemConfig\SystemConfigEntity;
use Swag\PayPal\Setting\Settings;
use Swag\PayPal\Test\Webhook\WebhookControllerTest;
use Swag\PayPal\Test\Webhook\WebhookServiceTest;

/**
 * @internal
 *
 * @extends AbstractRepoMock<SystemConfigCollection>
 */
#[Package('checkout')]
class SystemConfigRepoMock extends AbstractRepoMock
{
    public function getDefinition(): EntityDefinition
    {
        return new SystemConfigDefinition();
    }

    public function search(Criteria $criteria, Context $context): EntitySearchResult
    {
        $filter = $criteria->getFilters()[0];
        \assert($filter instanceof EqualsFilter);
        if ($context->hasExtension(WebhookControllerTest::EMPTY_TOKEN)
            || $filter->getValue() !== WebhookServiceTest::ALREADY_EXISTING_WEBHOOK_EXECUTE_TOKEN
        ) {
            return new EntitySearchResult(
                $this->getDefinition()->getEntityName(),
                0,
                new SystemConfigCollection([]),
                null,
                $criteria,
                $context
            );
        }

        return new EntitySearchResult(
            $this->getDefinition()->getEntityName(),
            1,
            new SystemConfigCollection([
                $this->createConfigEntity(),
            ]),
            null,
            $criteria,
            $context
        );
    }

    private function createConfigEntity(): SystemConfigEntity
    {
        $systemConfigEntity = new SystemConfigEntity();
        $systemConfigEntity->setId(Uuid::randomHex());
        $systemConfigEntity->setConfigurationKey(Settings::WEBHOOK_EXECUTE_TOKEN);
        $systemConfigEntity->setConfigurationValue(WebhookServiceTest::ALREADY_EXISTING_WEBHOOK_EXECUTE_TOKEN);

        return $systemConfigEntity;
    }
}
