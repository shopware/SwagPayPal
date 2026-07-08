<?php declare(strict_types=1);
/*
 * (c) shopware AG <info@shopware.com>
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Swag\PayPal\Test\Pos\Sync\Inventory;

use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Shopware\Core\Content\Product\Events\ProductStockAlteredEvent;
use Shopware\Core\Framework\Context;
use Shopware\Core\Framework\DataAbstractionLayer\EntityRepository;
use Shopware\Core\Framework\DataAbstractionLayer\Search\Criteria;
use Shopware\Core\Framework\DataAbstractionLayer\Search\IdSearchResult;
use Shopware\Core\Framework\Log\Package;
use Shopware\Core\Framework\Test\TestCaseBase\KernelTestBehaviour;
use Shopware\Core\Framework\Uuid\Uuid;
use Shopware\Core\System\SalesChannel\SalesChannelContext;
use Shopware\Core\Test\Generator;
use Swag\PayPal\Pos\MessageQueue\Message\InventoryUpdateMessage;
use Swag\PayPal\Pos\Sync\Inventory\StockSubscriber;
use Swag\PayPal\Test\Pos\ConstantsForTesting;
use Swag\PayPal\Test\Pos\Helper\SalesChannelTrait;
use Swag\PayPal\Test\Pos\Mock\MessageBusMock;
use Swag\PayPal\Test\Pos\Mock\Repositories\SalesChannelRepoMock;

/**
 * @internal
 */
#[Package('checkout')]
#[CoversClass(StockSubscriber::class)]
class StockSubscriberTest extends TestCase
{
    use KernelTestBehaviour;
    use SalesChannelTrait;

    private MockObject&EntityRepository $repositoryMock;

    public function testAddInventoryMessage(): void
    {
        $this->process(static function (StockSubscriber $stockSubscriber, SalesChannelContext $context): void {
            $event = new ProductStockAlteredEvent([
                ConstantsForTesting::PRODUCT_A_ID,
                ConstantsForTesting::PRODUCT_B_ID,
                ConstantsForTesting::PRODUCT_C_ID,
            ], $context->getContext());

            $stockSubscriber->updateInventory($event);
        });
    }

    public function testEmptyProductIds(): void
    {
        $this->process(static function (StockSubscriber $stockSubscriber, SalesChannelContext $context): void {
            $stockSubscriber->updateInventory(new ProductStockAlteredEvent([], $context->getContext()));
        }, false);
    }

    public function testNoPosSalesChannel(): void
    {
        $this->repositoryMock = $this->createMock(EntityRepository::class);
        $this->repositoryMock
            ->method('searchIds')
            ->willReturn(new IdSearchResult(0, [], new Criteria(), Context::createDefaultContext()));

        $this->process(static function (StockSubscriber $stockSubscriber, SalesChannelContext $context): void {
            $event = new ProductStockAlteredEvent([
                ConstantsForTesting::PRODUCT_A_ID,
                ConstantsForTesting::PRODUCT_B_ID,
                ConstantsForTesting::PRODUCT_C_ID,
            ], $context->getContext());

            $stockSubscriber->updateInventory($event);
        }, false);
    }

    public function testNotLiveContext(): void
    {
        $this->process(static function (StockSubscriber $stockSubscriber, SalesChannelContext $context): void {
            $event = new ProductStockAlteredEvent([
                ConstantsForTesting::PRODUCT_A_ID,
                ConstantsForTesting::PRODUCT_B_ID,
                ConstantsForTesting::PRODUCT_C_ID,
            ], $context->getContext()->createWithVersionId(Uuid::randomHex()));

            $stockSubscriber->updateInventory($event);
        }, false);
    }

    private function process(callable $callback, bool $shouldWork = true): void
    {
        $salesChannelContext = Generator::generateSalesChannelContext();

        if (!isset($this->repositoryMock)) {
            $salesChannel = $this->getSalesChannel($salesChannelContext->getContext());

            $salesChannelRepository = new SalesChannelRepoMock();
            $salesChannelRepository->getCollection()->clear();
            $salesChannelRepository->addMockEntity($salesChannel);
        } else {
            $salesChannelRepository = $this->repositoryMock;
        }

        $messageBus = new MessageBusMock();
        $callback(new StockSubscriber($messageBus, $salesChannelRepository), $salesChannelContext);

        $inventoryMessageCreated = false;
        foreach ($messageBus->getEnvelopes() as $envelope) {
            $message = $envelope->getMessage();
            if ($message instanceof InventoryUpdateMessage) {
                $inventoryMessageCreated = true;
                static::assertEqualsCanonicalizing(
                    [
                        ConstantsForTesting::PRODUCT_A_ID,
                        ConstantsForTesting::PRODUCT_B_ID,
                        ConstantsForTesting::PRODUCT_C_ID,
                    ],
                    \array_values($message->getIds())
                );
            }
        }

        static::assertSame($shouldWork, $inventoryMessageCreated);
    }
}
