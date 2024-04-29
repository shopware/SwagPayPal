<?php declare(strict_types=1);
/*
 * (c) shopware AG <info@shopware.com>
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Swag\PayPal\Test\Checkout\Order\Shipping;

use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Psr\Log\LoggerInterface;
use Shopware\Core\Checkout\Order\Aggregate\OrderDelivery\OrderDeliveryDefinition;
use Shopware\Core\Checkout\Order\OrderDefinition;
use Shopware\Core\Framework\Context;
use Shopware\Core\Framework\DataAbstractionLayer\DefinitionInstanceRegistry;
use Shopware\Core\Framework\DataAbstractionLayer\EntityWriteResult;
use Shopware\Core\Framework\DataAbstractionLayer\Event\EntityWrittenEvent;
use Shopware\Core\Framework\DataAbstractionLayer\Write\Command\ChangeSet;
use Shopware\Core\Framework\DataAbstractionLayer\Write\Command\ChangeSetAware;
use Shopware\Core\Framework\DataAbstractionLayer\Write\Command\DeleteCommand;
use Shopware\Core\Framework\DataAbstractionLayer\Write\Command\InsertCommand;
use Shopware\Core\Framework\DataAbstractionLayer\Write\Command\UpdateCommand;
use Shopware\Core\Framework\DataAbstractionLayer\Write\EntityExistence;
use Shopware\Core\Framework\DataAbstractionLayer\Write\Validation\PreWriteValidationEvent;
use Shopware\Core\Framework\DataAbstractionLayer\Write\WriteContext;
use Shopware\Core\Framework\Log\Package;
use Shopware\Core\Framework\Uuid\Uuid;
use Swag\PayPal\Checkout\Order\Shipping\Service\ShippingService;
use Swag\PayPal\Checkout\Order\Shipping\ShippingSubscriber;

/**
 * @internal
 */
#[Package('checkout')]
class ShippingSubscriberTest extends TestCase
{
    private const TEST_CODE = 'test_code';

    private ShippingService&MockObject $shippingService;

    public static function dataProviderWriteResult(): array
    {
        return [
            [
                new EntityWriteResult(Uuid::randomHex(), ['trackingCodes' => [self::TEST_CODE]], 'order_delivery', EntityWriteResult::OPERATION_INSERT, null, null),
                [self::TEST_CODE],
                [],
            ],
            [
                new EntityWriteResult(Uuid::randomHex(), ['trackingCodes' => [self::TEST_CODE]], 'order_delivery', EntityWriteResult::OPERATION_UPDATE, null, new ChangeSet(
                    ['tracking_codes' => null],
                    ['tracking_codes' => '["test_code"]'],
                    false,
                )),
                [self::TEST_CODE],
                [],
            ],
            [
                new EntityWriteResult(Uuid::randomHex(), ['trackingCodes' => null], 'order_delivery', EntityWriteResult::OPERATION_UPDATE, null, new ChangeSet(
                    ['tracking_codes' => '["test_code"]'],
                    ['tracking_codes' => null],
                    false,
                )),
                [],
                [self::TEST_CODE],
            ],
            [
                new EntityWriteResult(Uuid::randomHex(), [], 'order_delivery', EntityWriteResult::OPERATION_DELETE, null, new ChangeSet(
                    ['tracking_codes' => '["test_code"]'],
                    [],
                    true,
                )),
                null,
                [],
            ],
        ];
    }

    public function testTriggerChangeSet(): void
    {
        $orderDeliveryDefinition = new OrderDeliveryDefinition();
        $orderDeliveryDefinition->compile($this->createMock(DefinitionInstanceRegistry::class));
        $orderDefinition = new OrderDefinition();
        $orderDefinition->compile($this->createMock(DefinitionInstanceRegistry::class));

        $event = new PreWriteValidationEvent(WriteContext::createFromContext(Context::createDefaultContext()), [
            new DeleteCommand($orderDeliveryDefinition, ['id' => Uuid::randomBytes()], new EntityExistence('order_delivery', ['id' => Uuid::randomHex()], true, false, false, [])), // not touched, wrong command
            new UpdateCommand($orderDeliveryDefinition, [], ['id' => Uuid::randomBytes()], new EntityExistence('order_delivery', ['id' => Uuid::randomHex()], true, false, false, []), ''), // not touched, no payload
            new UpdateCommand($orderDefinition, ['tracking_codes' => '["code"]'], ['id' => Uuid::randomBytes()], new EntityExistence('order_delivery', ['id' => Uuid::randomHex()], true, false, false, []), ''), // not touched, wrong entity
            new InsertCommand($orderDeliveryDefinition, ['tracking_codes' => '["code"]'], ['id' => Uuid::randomBytes()], new EntityExistence('order_delivery', ['id' => Uuid::randomHex()], true, false, false, []), ''), // not touched, not changeset aware
            new UpdateCommand($orderDeliveryDefinition, ['tracking_codes' => '["code"]'], ['id' => Uuid::randomBytes()], new EntityExistence('order_delivery', ['id' => Uuid::randomHex()], true, false, false, []), ''), // touched
        ]);

        $this->getSubscriber()->triggerChangeSet($event);

        foreach ($event->getCommands() as $index => $command) {
            static::assertSame(
                $index >= 4,
                $command instanceof ChangeSetAware ? $command->requiresChangeSet() : false
            );
        }
    }

    public function testTriggerChangeSetNonLiveVersion(): void
    {
        $entityDefinition = new OrderDeliveryDefinition();
        $entityDefinition->compile($this->createMock(DefinitionInstanceRegistry::class));

        $event = new PreWriteValidationEvent(WriteContext::createFromContext(Context::createDefaultContext()->createWithVersionId(Uuid::randomHex())), [
            new UpdateCommand($entityDefinition, [], ['id' => Uuid::randomBytes()], new EntityExistence('order_delivery', ['id' => Uuid::randomHex()], true, false, false, []), ''), // not touched, no payload
        ]);

        $this->getSubscriber()->triggerChangeSet($event);

        foreach ($event->getCommands() as $command) {
            static::assertInstanceOf(ChangeSetAware::class, $command);
            static::assertFalse($command->requiresChangeSet());
        }
    }

    #[DataProvider('dataProviderWriteResult')]
    public function testOnOrderDeliveryWritten(EntityWriteResult $result, ?array $expectedAfter, array $expectedBefore): void
    {
        $event = new EntityWrittenEvent(
            'order_delivery',
            [
                $result,
            ],
            Context::createDefaultContext(),
            [],
        );

        $subscriber = $this->getSubscriber();
        $this->shippingService
            ->expects($expectedAfter === null ? static::never() : static::once())
            ->method('updateTrackingCodes')
            ->with($result->getPrimaryKey(), $expectedAfter, $expectedBefore, $event->getContext());

        $subscriber->onOrderDeliveryWritten($event);
    }

    #[DataProvider('dataProviderWriteResult')]
    public function testOnOrderDeliveryWrittenWithNonLiveVersion(EntityWriteResult $result): void
    {
        $event = new EntityWrittenEvent(
            'order_delivery',
            [
                $result,
            ],
            Context::createDefaultContext()->createWithVersionId(Uuid::randomHex()),
            [],
        );

        $subscriber = $this->getSubscriber();
        $this->shippingService
            ->expects(static::never())->method('updateTrackingCodes');

        $subscriber->onOrderDeliveryWritten($event);
    }

    private function getSubscriber(): ShippingSubscriber
    {
        $this->shippingService = $this->createMock(ShippingService::class);

        return new ShippingSubscriber(
            $this->shippingService,
            $this->createMock(LoggerInterface::class),
        );
    }
}
