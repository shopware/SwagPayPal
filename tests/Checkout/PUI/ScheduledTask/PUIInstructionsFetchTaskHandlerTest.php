<?php declare(strict_types=1);
/*
 * (c) shopware AG <info@shopware.com>
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Swag\PayPal\Test\Checkout\Payment\ScheduledTask;

use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Psr\Log\NullLogger;
use Shopware\Core\Checkout\Order\Aggregate\OrderTransaction\OrderTransactionStates;
use Shopware\Core\Framework\Context;
use Shopware\Core\Framework\DataAbstractionLayer\EntityRepository;
use Shopware\Core\Framework\DataAbstractionLayer\Search\Criteria;
use Shopware\Core\Framework\DataAbstractionLayer\Search\Filter\EqualsFilter;
use Shopware\Core\Framework\DataAbstractionLayer\Search\Filter\OrFilter;
use Shopware\Core\Framework\DataAbstractionLayer\Search\Filter\RangeFilter;
use Shopware\Core\Framework\DataAbstractionLayer\Search\IdSearchResult;
use Shopware\Core\Framework\Log\Package;
use Swag\PayPal\Checkout\PUI\MessageQueue\PUIInstructionsFetchMessage;
use Swag\PayPal\Checkout\PUI\ScheduledTask\PUIInstructionsFetchTaskHandler;
use Swag\PayPal\Util\Lifecycle\Method\PaymentMethodDataRegistry;
use Swag\PayPal\Util\Lifecycle\Method\PUIMethodData;
use Symfony\Component\Clock\NativeClock;
use Symfony\Component\Messenger\Envelope;
use Symfony\Component\Messenger\MessageBusInterface;

/**
 * @internal
 */
#[Package('checkout')]
#[CoversClass(PUIInstructionsFetchTaskHandler::class)]
class PUIInstructionsFetchTaskHandlerTest extends TestCase
{
    private EntityRepository&MockObject $orderTransactionRepository;

    private PaymentMethodDataRegistry&MockObject $paymentMethodDataRegistry;

    private MessageBusInterface&MockObject $bus;

    private PUIInstructionsFetchTaskHandler $handler;

    protected function setUp(): void
    {
        $this->orderTransactionRepository = $this->createMock(EntityRepository::class);
        $this->paymentMethodDataRegistry = $this->createMock(PaymentMethodDataRegistry::class);
        $this->bus = $this->createMock(MessageBusInterface::class);

        $this->handler = new PUIInstructionsFetchTaskHandler(
            $this->createMock(EntityRepository::class),
            new NullLogger(),
            $this->orderTransactionRepository,
            $this->paymentMethodDataRegistry,
            $this->bus,
            new NativeClock(),
        );
    }

    public function testRun(): void
    {
        $puiMethodData = static::createStub(PUIMethodData::class);

        $this->paymentMethodDataRegistry
            ->expects($this->once())
            ->method('getPaymentMethod')
            ->willReturn($puiMethodData);

        $criteria = new Criteria();

        $searchResult = new IdSearchResult(
            1,
            ['test-id' => ['primaryKey' => 'test-id', 'data' => []]],
            $criteria,
            Context::createDefaultContext()
        );

        $this->orderTransactionRepository
            ->expects($this->once())
            ->method('searchIds')
            ->willReturnCallback(static function ($newCriteria) use (&$criteria, $searchResult) {
                $criteria = $newCriteria;

                return $searchResult;
            });

        $this->bus->expects($this->once())
            ->method('dispatch')
            ->willReturnCallback(static function (PUIInstructionsFetchMessage $message) {
                static::assertSame('test-id', $message->getTransactionId());

                return new Envelope($message);
            });

        $this->handler->run();

        $this->assertCriteria($criteria);
    }

    private function assertCriteria(Criteria $criteria): void
    {
        $filters = $criteria->getFilters();
        static::assertCount(3, $filters);

        static::assertInstanceOf(EqualsFilter::class, $filters[0]);
        static::assertSame('paymentMethod.handlerIdentifier', $filters[0]->getField());

        static::assertInstanceOf(OrFilter::class, $filters[1]);
        $queries = $filters[1]->getQueries();
        static::assertCount(2, $queries);

        static::assertInstanceOf(EqualsFilter::class, $queries[0]);
        static::assertSame('stateMachineState.technicalName', $queries[0]->getField());
        static::assertSame(OrderTransactionStates::STATE_AUTHORIZED, $queries[0]->getValue());
        static::assertInstanceOf(EqualsFilter::class, $queries[1]);
        static::assertSame('stateMachineState.technicalName', $queries[1]->getField());
        static::assertSame(OrderTransactionStates::STATE_IN_PROGRESS, $queries[1]->getValue());

        static::assertInstanceOf(OrFilter::class, $filters[2]);
        $queries = $filters[2]->getQueries();
        static::assertCount(2, $queries);

        static::assertInstanceOf(RangeFilter::class, $queries[0]);
        static::assertSame('createdAt', $queries[0]->getField());
        static::assertInstanceOf(RangeFilter::class, $queries[1]);
        static::assertSame('updatedAt', $queries[1]->getField());
    }
}
