<?php declare(strict_types=1);
/*
 * (c) shopware AG <info@shopware.com>
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Swag\PayPal\Test\Checkout\Order\Shipping;

use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Shopware\Core\Checkout\Order\Aggregate\OrderDelivery\OrderDeliveryDefinition;
use Shopware\Core\Checkout\Order\OrderDefinition;
use Shopware\Core\Framework\Context;
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
use Swag\PayPal\Checkout\Order\Shipping\MessageQueue\ShippingInformationMessage;
use Swag\PayPal\Checkout\Order\Shipping\ShippingSubscriber;
use Symfony\Component\Messenger\Envelope;
use Symfony\Component\Messenger\MessageBusInterface;

/**
 * @internal
 */
#[Package('checkout')]
class ShippingSubscriberTest extends TestCase
{
    private const TEST_CODE = 'test_code';

    private ShippingSubscriber $subscriber;

    private MessageBusInterface&MockObject $bus;

    protected function setUp(): void
    {
        $this->bus = $this->createMock(MessageBusInterface::class);
        $this->subscriber = new ShippingSubscriber($this->bus);
    }

    public static function dataProviderWriteResult(): \Generator
    {
        yield 'inserted one tracking code, without changeset' => [
            new EntityWriteResult(Uuid::randomHex(), ['trackingCodes' => [self::TEST_CODE]], 'order_delivery', EntityWriteResult::OPERATION_INSERT, null, null),
            [self::TEST_CODE],
            [],
        ];

        yield 'updated one tracking code, with changeset' => [
            new EntityWriteResult(Uuid::randomHex(), ['trackingCodes' => [self::TEST_CODE]], 'order_delivery', EntityWriteResult::OPERATION_UPDATE, null, new ChangeSet(
                ['tracking_codes' => null],
                ['tracking_codes' => '["test_code"]'],
                false,
            )),
            [self::TEST_CODE],
            [],
        ];

        yield 'deleted one existing tracking code, with changeset' => [
            new EntityWriteResult(Uuid::randomHex(), ['trackingCodes' => null], 'order_delivery', EntityWriteResult::OPERATION_UPDATE, null, new ChangeSet(
                ['tracking_codes' => '["test_code"]'],
                ['tracking_codes' => null],
                false,
            )),
            [],
            [self::TEST_CODE],
        ];

        yield 'deleted one not existing tracking code, with changeset' => [
            new EntityWriteResult(Uuid::randomHex(), [], 'order_delivery', EntityWriteResult::OPERATION_DELETE, null, new ChangeSet(
                ['tracking_codes' => '["test_code"]'],
                [],
                true,
            )),
            null,
            [],
        ];
    }

    public function testTriggerChangeSet(): void
    {
        $event = new PreWriteValidationEvent(WriteContext::createFromContext(Context::createDefaultContext()), [
            new DeleteCommand(new OrderDeliveryDefinition(), ['id' => Uuid::randomBytes()], new EntityExistence('order_delivery', ['id' => Uuid::randomHex()], true, false, false, [])), // not touched, wrong command
            new UpdateCommand(new OrderDeliveryDefinition(), [], ['id' => Uuid::randomBytes()], new EntityExistence('order_delivery', ['id' => Uuid::randomHex()], true, false, false, []), ''), // not touched, no payload
            new UpdateCommand(new OrderDefinition(), ['tracking_codes' => '["code"]'], ['id' => Uuid::randomBytes()], new EntityExistence('order_delivery', ['id' => Uuid::randomHex()], true, false, false, []), ''), // not touched, wrong entity
            new InsertCommand(new OrderDeliveryDefinition(), ['tracking_codes' => '["code"]'], ['id' => Uuid::randomBytes()], new EntityExistence('order_delivery', ['id' => Uuid::randomHex()], true, false, false, []), ''), // not touched, not changeset aware
            new UpdateCommand(new OrderDeliveryDefinition(), ['tracking_codes' => '["code"]'], ['id' => Uuid::randomBytes()], new EntityExistence('order_delivery', ['id' => Uuid::randomHex()], true, false, false, []), ''), // touched
        ]);

        $this->subscriber->triggerChangeSet($event);

        foreach ($event->getCommands() as $index => $command) {
            static::assertSame(
                $index >= 4,
                $command instanceof ChangeSetAware ? $command->requiresChangeSet() : false
            );
        }
    }

    public function testTriggerChangeSetNonLiveVersion(): void
    {
        $event = new PreWriteValidationEvent(WriteContext::createFromContext(Context::createDefaultContext()->createWithVersionId(Uuid::randomHex())), [
            new UpdateCommand(new OrderDeliveryDefinition(), [], ['id' => Uuid::randomBytes()], new EntityExistence('order_delivery', ['id' => Uuid::randomHex()], true, false, false, []), ''), // not touched, no payload
        ]);

        $this->subscriber->triggerChangeSet($event);

        foreach ($event->getCommands() as $command) {
            static::assertInstanceOf(ChangeSetAware::class, $command);
            static::assertFalse($command->requiresChangeSet());
        }
    }

    /**
     * @dataProvider dataProviderWriteResult
     */
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

        $this->bus
            ->expects($expectedAfter === null ? static::never() : static::once())
            ->method('dispatch')
            ->willReturnCallback(function (ShippingInformationMessage $message) use (&$result): Envelope {
                static::assertSame($result->getPrimaryKey(), $message->getOrderDeliveryId());

                return new Envelope($message);
            });

        $this->subscriber->onOrderDeliveryWritten($event);
    }

    /**
     * @dataProvider dataProviderWriteResult
     */
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

        $this->bus
            ->expects(static::never())
            ->method('dispatch');

        $this->subscriber->onOrderDeliveryWritten($event);
    }
}
