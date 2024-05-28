<?php declare(strict_types=1);
/*
 * (c) shopware AG <info@shopware.com>
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Swag\PayPal\Test\Checkout\PUI\MessageQueue;

use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Shopware\Core\Checkout\Order\Aggregate\OrderTransaction\OrderTransactionEntity;
use Shopware\Core\Checkout\Order\Aggregate\OrderTransaction\OrderTransactionStates;
use Shopware\Core\Checkout\Order\OrderEntity;
use Shopware\Core\Framework\Context;
use Shopware\Core\Framework\DataAbstractionLayer\EntityCollection;
use Shopware\Core\Framework\DataAbstractionLayer\EntityRepository;
use Shopware\Core\Framework\DataAbstractionLayer\Search\Criteria;
use Shopware\Core\Framework\DataAbstractionLayer\Search\EntitySearchResult;
use Shopware\Core\Framework\DataAbstractionLayer\Search\Filter\EqualsFilter;
use Shopware\Core\Framework\DataAbstractionLayer\Search\Filter\OrFilter;
use Shopware\Core\Framework\Log\Package;
use Swag\PayPal\Checkout\PUI\MessageQueue\PUIInstructionsFetchMessage;
use Swag\PayPal\Checkout\PUI\MessageQueue\PUIInstructionsFetchMessageHandler;
use Swag\PayPal\Checkout\PUI\Service\PUIInstructionsFetchService;

/**
 * @internal
 */
#[Package('checkout')]
#[CoversClass(PUIInstructionsFetchMessageHandler::class)]
class PUIInstructionsFetchMessageHandlerTest extends TestCase
{
    private EntityRepository&MockObject $orderTransactionRepository;

    private PUIInstructionsFetchService&MockObject $instructionsService;

    private PUIInstructionsFetchMessageHandler $handler;

    protected function setUp(): void
    {
        $this->orderTransactionRepository = $this->createMock(EntityRepository::class);
        $this->instructionsService = $this->createMock(PUIInstructionsFetchService::class);

        $this->handler = new PUIInstructionsFetchMessageHandler(
            $this->orderTransactionRepository,
            $this->instructionsService,
        );
    }

    public function testInvoke(): void
    {
        $message = new PUIInstructionsFetchMessage('test-id');

        $orderTransaction = (new OrderTransactionEntity())->assign([
            'id' => 'test-id',
            'order' => (new OrderEntity())->assign([
                'salesChannelId' => 'sales-channel-id',
            ]),
        ]);

        $criteria = new Criteria();

        $searchResult = new EntitySearchResult(
            'order_transaction',
            1,
            new EntityCollection([$orderTransaction]),
            null,
            $criteria,
            Context::createDefaultContext()
        );

        $this->orderTransactionRepository
            ->expects(static::once())
            ->method('search')
            ->willReturnCallback(function ($newCriteria) use (&$criteria, $searchResult) {
                $criteria = $newCriteria;

                return $searchResult;
            });

        $this->instructionsService
            ->expects(static::once())
            ->method('fetchPUIInstructions')
            ->with($orderTransaction, 'sales-channel-id');

        ($this->handler)($message);

        $this->assertCriteria($criteria);
    }

    public function testInvokeWithoutTransaction(): void
    {
        $message = new PUIInstructionsFetchMessage('test-id');

        $searchResult = new EntitySearchResult(
            'order_transaction',
            0,
            new EntityCollection([]),
            null,
            new Criteria(),
            Context::createDefaultContext()
        );

        $this->orderTransactionRepository
            ->expects(static::once())
            ->method('search')
            ->willReturn($searchResult);

        $this->instructionsService
            ->expects(static::never())
            ->method('fetchPUIInstructions');

        ($this->handler)($message);
    }

    private function assertCriteria(Criteria $criteria): void
    {
        static::assertEquals(1, $criteria->getLimit());
        static::assertTrue($criteria->hasAssociation('order'));

        $filters = $criteria->getFilters();
        static::assertCount(1, $filters);

        static::assertInstanceOf(OrFilter::class, $filters[0]);
        $queries = $filters[0]->getQueries();
        static::assertCount(2, $queries);

        static::assertInstanceOf(EqualsFilter::class, $queries[0]);
        static::assertSame('stateMachineState.technicalName', $queries[0]->getField());
        static::assertSame(OrderTransactionStates::STATE_AUTHORIZED, $queries[0]->getValue());

        static::assertInstanceOf(EqualsFilter::class, $queries[1]);
        static::assertSame('stateMachineState.technicalName', $queries[1]->getField());
        static::assertSame(OrderTransactionStates::STATE_IN_PROGRESS, $queries[1]->getValue());
    }
}
