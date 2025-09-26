<?php declare(strict_types=1);
/*
 * (c) shopware AG <info@shopware.com>
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Swag\PayPal\AgentCommerce\Subscriber;

use Shopware\Administration\Notification\NotificationCollection;
use Shopware\Core\Framework\Api\Context\AdminApiSource;
use Shopware\Core\Framework\Context;
use Shopware\Core\Framework\DataAbstractionLayer\EntityRepository;
use Shopware\Core\Framework\DataAbstractionLayer\EntityWriteResult;
use Shopware\Core\Framework\DataAbstractionLayer\Event\EntityWrittenEvent;
use Shopware\Core\Framework\DataAbstractionLayer\Search\Criteria;
use Shopware\Core\Framework\DataAbstractionLayer\Search\Filter\EqualsFilter;
use Shopware\Core\Framework\Log\Package;
use Shopware\Core\Framework\Uuid\Uuid;
use Shopware\Core\System\SalesChannel\SalesChannelCollection;
use Swag\PayPal\AgentCommerce\Exception\HoneyWebhookException;
use Swag\PayPal\AgentCommerce\HoneyWebhookService;
use Swag\PayPal\SwagPayPal;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;

/**
 * @internal
 */
#[Package('checkout')]
class WebhookSubscriber implements EventSubscriberInterface
{
    /**
     * @param EntityRepository<SalesChannelCollection> $salesChannelRepository
     * @param EntityRepository<NotificationCollection> $notificationRepository
     */
    public function __construct(
        private readonly EntityRepository $salesChannelRepository,
        private readonly HoneyWebhookService $webhookService,
        private readonly EntityRepository $notificationRepository, // @phpstan-ignore parameter.deprecatedClass, property.deprecatedClass
    ) {
    }

    public static function getSubscribedEvents(): array
    {
        return [
            'sales_channel.written' => 'handleWebhookLifecycle',
        ];
    }

    public function handleWebhookLifecycle(EntityWrittenEvent $event): void
    {
        $mapped = [];
        foreach ($event->getWriteResults() as $writeResult) {
            /** @var string $id */
            $id = $writeResult->getPrimaryKey();
            if ($writeResult->getOperation() === EntityWriteResult::OPERATION_DELETE) {
                $mapped[$id] = false;

                continue;
            }

            $active = $writeResult->getProperty('active');
            if ($active === null) {
                continue;
            }

            $mapped[$id] = $active;
        }

        if (empty($mapped)) {
            return;
        }

        $criteria = new Criteria(array_keys($mapped));
        $criteria->addFilter(new EqualsFilter('typeId', SwagPayPal::SALES_CHANNEL_TYPE_AGENT_COMMERCE));

        $userId = null;
        $context = $event->getContext();
        $source = $context->getSource();
        if ($source instanceof AdminApiSource) {
            $userId = $source->getUserId();
        }

        /** @var list<string> $salesChannelIds */
        $salesChannelIds = $this->salesChannelRepository->searchIds($criteria, $context)->getIds();
        foreach ($salesChannelIds as $salesChannelId) {
            try {
                if ($mapped[$salesChannelId]) {
                    $result = $this->webhookService->register($salesChannelId, $context);
                } else {
                    $result = $this->webhookService->deregister($salesChannelId);
                }

                if (!$userId) {
                    continue;
                }

                $notification = [
                    'id' => Uuid::randomHex(),
                    'status' => $result->success ? 'success' : 'error',
                    'message' => 'PayPal agent commerce: ' . $result->message,
                    'requiredPrivileges' => [],
                    'createdByUserId' => $userId,
                ];
            } catch (HoneyWebhookException $e) {
                $notification = [
                    'id' => Uuid::randomHex(),
                    'status' => 'error',
                    'message' => 'PayPal agent commerce: ' . $e->getErrorCode(),
                    'requiredPrivileges' => [],
                    'createdByUserId' => $userId,
                ];
            }

            $context->scope(Context::SYSTEM_SCOPE, function (Context $context) use ($notification): void {
                $this->notificationRepository->create([$notification], $context);
            });
        }
    }
}
