<?php declare(strict_types=1);
/*
 * (c) shopware AG <info@shopware.com>
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Swag\PayPal\Test\AgentCommerce\Subscriber;

use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;
use Shopware\Core\Framework\Api\Context\AdminApiSource;
use Shopware\Core\Framework\Context;
use Shopware\Core\Framework\DataAbstractionLayer\EntityRepository;
use Shopware\Core\Framework\DataAbstractionLayer\EntityWriteResult;
use Shopware\Core\Framework\DataAbstractionLayer\Event\EntityWrittenEvent;
use Shopware\Core\Framework\DataAbstractionLayer\Search\Criteria;
use Shopware\Core\Framework\DataAbstractionLayer\Search\IdSearchResult;
use Shopware\Core\Framework\Log\Package;
use Shopware\Core\Framework\Uuid\Uuid;
use Shopware\Core\System\SalesChannel\SalesChannelDefinition;
use Swag\PayPal\AgentCommerce\HoneyWebhookResult;
use Swag\PayPal\AgentCommerce\HoneyWebhookService;
use Swag\PayPal\AgentCommerce\Subscriber\WebhookSubscriber;

/**
 * @internal
 */
#[Package('checkout')]
#[CoversClass(WebhookSubscriber::class)]
class WebhookSubscriberTest extends TestCase
{
    public function testTest(): void
    {
        $deleteResult = new EntityWriteResult(
            $deleteId = Uuid::randomHex(),
            [],
            SalesChannelDefinition::ENTITY_NAME,
            EntityWriteResult::OPERATION_DELETE
        );

        $noPayloadResult = new EntityWriteResult(
            Uuid::randomHex(),
            ['other' => 'properties'],
            SalesChannelDefinition::ENTITY_NAME,
            EntityWriteResult::OPERATION_UPDATE
        );

        $activateResult = new EntityWriteResult(
            $activateId = Uuid::randomHex(),
            ['active' => true],
            SalesChannelDefinition::ENTITY_NAME,
            EntityWriteResult::OPERATION_UPDATE
        );

        $deactivateResult = new EntityWriteResult(
            $deactiveId = Uuid::randomHex(),
            ['active' => false],
            SalesChannelDefinition::ENTITY_NAME,
            EntityWriteResult::OPERATION_UPDATE
        );

        $event = new EntityWrittenEvent(
            SalesChannelDefinition::ENTITY_NAME,
            [$deleteResult, $noPayloadResult, $activateResult, $deactivateResult],
            Context::createCLIContext(new AdminApiSource(Uuid::randomHex())),
            []
        );

        $salesChannelRepository = $this->createMock(EntityRepository::class);
        $salesChannelRepository
            ->expects($this->once())
            ->method('searchIds')
            ->willReturnCallback(static function (Criteria $criteria) use ($deleteId, $activateId, $deactiveId) {
                static::assertSame([$deleteId, $activateId, $deactiveId], $criteria->getIds());

                $data = [
                    ['primaryKey' => $activateId, 'data' => []],
                    ['primaryKey' => $deactiveId, 'data' => []],
                ];

                return new IdSearchResult(2, $data, $criteria, Context::createCLIContext());
            });

        $webhookResult = new HoneyWebhookResult(true, 'success message', null);
        $webhook = $this->createMock(HoneyWebhookService::class);
        $webhook
            ->expects($this->once())
            ->method('register')
            ->with($activateId)
            ->willReturn($webhookResult);
        $webhook
            ->expects($this->exactly(2))
            ->method('deregister')
            ->willReturn($webhookResult);

        $notificationRepository = $this->createMock(EntityRepository::class);
        $notificationRepository
            ->expects($this->exactly(3))
            ->method('create');

        $subscriber = new WebhookSubscriber(
            $salesChannelRepository,
            $webhook,
            $notificationRepository,
        );

        $subscriber->handleWebhookLifecycle($event);
    }

    public function testEmptyWriteResult(): void
    {
        $salesChannelRepository = $this->createMock(EntityRepository::class);
        $salesChannelRepository
            ->expects($this->never())
            ->method('searchIds');

        $webhook = $this->createMock(HoneyWebhookService::class);
        $webhook
            ->expects($this->never())
            ->method('register');
        $webhook
            ->expects($this->never())
            ->method('deregister');

        $notificationRepository = $this->createMock(EntityRepository::class);
        $notificationRepository
            ->expects($this->never())
            ->method('create');

        $subscriber = new WebhookSubscriber(
            $salesChannelRepository,
            $webhook,
            $notificationRepository,
        );

        $event = new EntityWrittenEvent(SalesChannelDefinition::ENTITY_NAME, [], Context::createCLIContext(), []);

        $subscriber->handleWebhookLifecycle($event);
    }
}
