<?php declare(strict_types=1);
/*
 * (c) shopware AG <info@shopware.com>
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Swag\PayPal\Test\Checkout\Order\Shipping;

use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Psr\Log\LoggerInterface;
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
use Shopware\Core\Framework\Uuid\Uuid;
use Swag\PayPal\Checkout\Order\Shipping\Service\ShippingService;
use Swag\PayPal\Checkout\Order\Shipping\ShippingSubscriber;

/**
 * @internal
 */
class ShippingSubscriberTest extends TestCase
{
    private const TEST_CODE = 'test_code';

    /**
     * @var ShippingService&MockObject
     */
    private $shippingService;

    public function testTriggerChangeSet(): void
    {
        $event = new PreWriteValidationEvent(WriteContext::createFromContext(Context::createDefaultContext()), [
            new DeleteCommand(new OrderDeliveryDefinition(), [Uuid::randomHex()], new EntityExistence('order_delivery', ['id' => Uuid::randomHex()], true, false, false, [])), // not touched, wrong command
            new UpdateCommand(new OrderDeliveryDefinition(), [], [Uuid::randomHex()], new EntityExistence('order_delivery', ['id' => Uuid::randomHex()], true, false, false, []), ''), // not touched, no payload
            new UpdateCommand(new OrderDefinition(), ['tracking_codes' => '["code"]'], [Uuid::randomHex()], new EntityExistence('order_delivery', ['id' => Uuid::randomHex()], true, false, false, []), ''), // not touched, wrong entity
            new InsertCommand(new OrderDeliveryDefinition(), ['tracking_codes' => '["code"]'], [Uuid::randomHex()], new EntityExistence('order_delivery', ['id' => Uuid::randomHex()], true, false, false, []), ''), // not touched, not changeset aware
            new UpdateCommand(new OrderDeliveryDefinition(), ['tracking_codes' => '["code"]'], [Uuid::randomHex()], new EntityExistence('order_delivery', ['id' => Uuid::randomHex()], true, false, false, []), ''), // touched
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
        $event = new PreWriteValidationEvent(WriteContext::createFromContext(Context::createDefaultContext()->createWithVersionId(Uuid::randomHex())), [
            new UpdateCommand(new OrderDeliveryDefinition(), [], [Uuid::randomHex()], new EntityExistence('order_delivery', ['id' => Uuid::randomHex()], true, false, false, []), ''), // not touched, no payload
        ]);

        $this->getSubscriber()->triggerChangeSet($event);

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

        $subscriber = $this->getSubscriber();
        $this->shippingService
            ->expects($expectedAfter === null ? static::never() : static::once())
            ->method('updateTrackingCodes')
            ->with($result->getPrimaryKey(), $expectedAfter, $expectedBefore, $event->getContext());

        $subscriber->onOrderDeliveryWritten($event);
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

        $subscriber = $this->getSubscriber();
        $this->shippingService
            ->expects(static::never())->method('updateTrackingCodes');

        $subscriber->onOrderDeliveryWritten($event);
    }

    public function dataProviderWriteResult(): array
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

    private function getSubscriber(): ShippingSubscriber
    {
        $this->shippingService = $this->createMock(ShippingService::class);

        return new ShippingSubscriber(
            $this->shippingService,
            $this->createMock(LoggerInterface::class),
        );
    }
}
