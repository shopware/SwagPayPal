<?php declare(strict_types=1);
/*
 * (c) shopware AG <info@shopware.com>
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Swag\PayPal\Checkout\Order\Shipping;

use Psr\Log\LoggerInterface;
use Shopware\Core\Checkout\Order\Aggregate\OrderDelivery\OrderDeliveryDefinition;
use Shopware\Core\Checkout\Order\OrderEvents;
use Shopware\Core\Defaults;
use Shopware\Core\Framework\DataAbstractionLayer\EntityWriteResult;
use Shopware\Core\Framework\DataAbstractionLayer\Event\EntityWrittenEvent;
use Shopware\Core\Framework\DataAbstractionLayer\Write\Command\ChangeSetAware;
use Shopware\Core\Framework\DataAbstractionLayer\Write\Validation\PreWriteValidationEvent;
use Swag\PayPal\Checkout\Order\Shipping\Service\ShippingService;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;

class ShippingSubscriber implements EventSubscriberInterface
{
    private ShippingService $shippingService;

    private LoggerInterface $logger;

    public function __construct(
        ShippingService $shippingService,
        LoggerInterface $logger
    ) {
        $this->shippingService = $shippingService;
        $this->logger = $logger;
    }

    public static function getSubscribedEvents(): array
    {
        return [
            PreWriteValidationEvent::class => 'triggerChangeSet',
            OrderEvents::ORDER_DELIVERY_WRITTEN_EVENT => 'onOrderDeliveryWritten',
        ];
    }

    public function triggerChangeSet(PreWriteValidationEvent $event): void
    {
        if ($event->getContext()->getVersionId() !== Defaults::LIVE_VERSION) {
            return;
        }

        foreach ($event->getCommands() as $command) {
            if (!$command instanceof ChangeSetAware) {
                continue;
            }

            if ($command->getDefinition()->getEntityName() !== OrderDeliveryDefinition::ENTITY_NAME) {
                continue;
            }

            if (!isset($command->getPayload()['tracking_codes'])) {
                continue;
            }

            $command->requestChangeSet();
        }
    }

    public function onOrderDeliveryWritten(EntityWrittenEvent $event): void
    {
        if ($event->getContext()->getVersionId() !== Defaults::LIVE_VERSION) {
            return;
        }

        foreach ($event->getWriteResults() as $writeResult) {
            if ($writeResult->getOperation() === EntityWriteResult::OPERATION_DELETE) {
                continue;
            }

            $before = [];
            $changeSet = $writeResult->getChangeSet();
            if ($changeSet !== null && !$changeSet->hasChanged('tracking_codes')) {
                continue;
            }

            if ($changeSet !== null) {
                $codesBefore = $changeSet->getBefore('tracking_codes') ?? [];
                $before = !\is_array($codesBefore) ? \json_decode($codesBefore) : [];
            }

            $orderDeliveryId = $writeResult->getPrimaryKey();
            $after = $writeResult->getProperty('trackingCodes') ?? [];
            if (!\is_string($orderDeliveryId) || !\is_array($after) || !\is_array($before)) {
                continue;
            }

            try {
                $this->shippingService->updateTrackingCodes($orderDeliveryId, $after, $before, $event->getContext());
            } catch (\Throwable $e) {
                $this->logger->warning('Could not update tracking codes', [
                    'exception' => $e,
                ]);
            }
        }
    }
}
