<?php declare(strict_types=1);
/*
 * (c) shopware AG <info@shopware.com>
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Swag\PayPal\Checkout\Order\Shipping;

use Shopware\Core\Checkout\Order\Aggregate\OrderDelivery\OrderDeliveryDefinition;
use Shopware\Core\Checkout\Order\OrderEvents;
use Shopware\Core\Defaults;
use Shopware\Core\Framework\DataAbstractionLayer\EntityWriteResult;
use Shopware\Core\Framework\DataAbstractionLayer\Event\EntityWrittenEvent;
use Shopware\Core\Framework\DataAbstractionLayer\Write\Command\ChangeSetAware;
use Shopware\Core\Framework\DataAbstractionLayer\Write\Validation\PreWriteValidationEvent;
use Shopware\Core\Framework\Log\Package;
use Swag\PayPal\Checkout\Order\Shipping\MessageQueue\ShippingInformationMessage;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\Messenger\MessageBusInterface;

/**
 * @internal
 */
#[Package('checkout')]
class ShippingSubscriber implements EventSubscriberInterface
{
    public function __construct(
        private readonly MessageBusInterface $bus,
    ) {
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

            if ($command->getEntityName() !== OrderDeliveryDefinition::ENTITY_NAME) {
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

            $changeSet = $writeResult->getChangeSet();
            if ($changeSet && !$changeSet->hasChanged('tracking_codes')) {
                continue;
            }

            $orderDeliveryId = $writeResult->getPrimaryKey();
            if (!\is_string($orderDeliveryId)) {
                continue;
            }

            $this->bus->dispatch(new ShippingInformationMessage($orderDeliveryId));
        }
    }
}
