<?php declare(strict_types=1);
/*
 * (c) shopware AG <info@shopware.com>
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Swag\PayPal\Test\Checkout\PUI\SalesChannel;

use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Shopware\Core\Checkout\Order\Aggregate\OrderTransaction\OrderTransactionEntity;
use Shopware\Core\Checkout\Order\OrderException;
use Shopware\Core\Framework\Context;
use Shopware\Core\Framework\DataAbstractionLayer\EntityCollection;
use Shopware\Core\Framework\DataAbstractionLayer\EntityRepository;
use Shopware\Core\Framework\DataAbstractionLayer\Search\Criteria;
use Shopware\Core\Framework\DataAbstractionLayer\Search\EntitySearchResult;
use Shopware\Core\Framework\Log\Package;
use Shopware\Core\Test\Generator;
use Swag\PayPal\Checkout\PUI\SalesChannel\PUIPaymentInstructionsRoute;
use Swag\PayPal\Checkout\PUI\Service\PUIInstructionsFetchService;

/**
 * @internal
 */
#[Package('checkout')]
class PUIPaymentInstructionsRouteTest extends TestCase
{
    private EntityRepository&MockObject $orderTransactionRepository;

    private PUIInstructionsFetchService&MockObject $instructionsService;

    private PUIPaymentInstructionsRoute $route;

    protected function setUp(): void
    {
        $this->orderTransactionRepository = $this->createMock(EntityRepository::class);
        $this->instructionsService = $this->createMock(PUIInstructionsFetchService::class);

        $this->route = new PUIPaymentInstructionsRoute(
            $this->orderTransactionRepository,
            $this->instructionsService,
        );
    }

    public function testGetPaymentInstructions(): void
    {
        $orderTransaction = (new OrderTransactionEntity())->assign([
            'id' => 'test-id',
        ]);

        $searchResult = new EntitySearchResult(
            'order_transaction',
            1,
            new EntityCollection([$orderTransaction]),
            null,
            new Criteria(),
            Context::createDefaultContext()
        );

        $this->orderTransactionRepository
            ->expects(static::once())
            ->method('search')
            ->willReturnCallback(static function ($criteria) use ($searchResult) {
                static::assertEquals(['test-id'], $criteria->getIds());

                return $searchResult;
            });

        $this->instructionsService
            ->expects(static::once())
            ->method('fetchPUIInstructions')
            ->with($orderTransaction);

        $this->route->getPaymentInstructions('test-id', Generator::createSalesChannelContext());
    }

    public function testGetPaymentInstructionsWithoutTransaction(): void
    {
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

        static::expectException(OrderException::class);
        static::expectExceptionMessage('Could not find order transaction with id "test-id"');

        $this->route->getPaymentInstructions('test-id', Generator::createSalesChannelContext());
    }
}
