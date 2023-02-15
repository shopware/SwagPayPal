<?php declare(strict_types=1);
/*
 * (c) shopware AG <info@shopware.com>
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Swag\PayPal\Test\Pos\Sync\Inventory;

use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Shopware\Core\Content\Product\ProductCollection;
use Shopware\Core\Framework\Context;
use Shopware\Core\Framework\Uuid\Uuid;
use Swag\PayPal\Pos\Api\Inventory\Status;
use Swag\PayPal\Pos\Api\Service\Converter\UuidConverter;
use Swag\PayPal\Pos\Sync\Context\InventoryContext;
use Swag\PayPal\Pos\Sync\Context\InventoryContextFactory;
use Swag\PayPal\Pos\Sync\Inventory\LocalUpdater;
use Swag\PayPal\Pos\Sync\Inventory\RemoteUpdater;
use Swag\PayPal\Pos\Sync\InventorySyncer;
use Swag\PayPal\Test\Pos\Mock\Repositories\PosInventoryRepoMock;

/**
 * @internal
 */
class InventorySyncerTest extends TestCase
{
    use InventoryTrait;

    private InventorySyncer $inventorySyncer;

    private MockObject $inventoryRepository;

    private MockObject $localUpdater;

    private MockObject $remoteUpdater;

    private InventoryContext $inventoryContext;

    public function setUp(): void
    {
        $context = Context::createDefaultContext();

        $salesChannel = $this->getSalesChannel($context);

        $this->inventoryRepository = $this->createPartialMock(PosInventoryRepoMock::class, ['upsert']);
        $this->localUpdater = $this->createMock(LocalUpdater::class);
        $this->remoteUpdater = $this->createMock(RemoteUpdater::class);

        $uuidConverter = new UuidConverter();

        $this->inventoryContext = new InventoryContext(
            $uuidConverter->convertUuidToV1(Uuid::randomHex()),
            $uuidConverter->convertUuidToV1(Uuid::randomHex()),
            $uuidConverter->convertUuidToV1(Uuid::randomHex()),
            $uuidConverter->convertUuidToV1(Uuid::randomHex()),
            new Status(),
        );
        $this->inventoryContext->setSalesChannel($salesChannel);

        $this->inventorySyncer = new InventorySyncer(
            $this->createStub(InventoryContextFactory::class),
            $this->localUpdater,
            $this->remoteUpdater,
            $this->inventoryRepository
        );
    }

    public function testInventorySyncLocal(): void
    {
        $product = $this->getSingleProduct();
        $this->localUpdater->method('updateLocal')->willReturn(new ProductCollection([$product]));
        $this->remoteUpdater->method('updateRemote')->willReturn(new ProductCollection());

        $this->inventoryRepository->expects(static::once())->method('upsert');

        $this->inventorySyncer->sync(
            new ProductCollection([$product]),
            $this->inventoryContext
        );
    }

    public function testInventorySyncRemote(): void
    {
        $product = $this->getSingleProduct();
        $this->localUpdater->method('updateLocal')->willReturn(new ProductCollection());
        $this->remoteUpdater->method('updateRemote')->willReturn(new ProductCollection([$product]));

        $this->inventoryRepository->expects(static::once())->method('upsert');

        $this->inventorySyncer->sync(
            new ProductCollection([$product]),
            $this->inventoryContext
        );
    }

    public function testInventorySyncBoth(): void
    {
        $product = $this->getSingleProduct();
        $this->localUpdater->method('updateLocal')->willReturn(new ProductCollection([$product]));
        $this->remoteUpdater->method('updateRemote')->willReturn(new ProductCollection([$product]));

        $this->inventoryRepository->expects(static::exactly(2))->method('upsert');

        $this->inventorySyncer->sync(
            new ProductCollection([$product]),
            $this->inventoryContext
        );
    }

    public function testInventorySyncNone(): void
    {
        $product = $this->getSingleProduct();
        $this->localUpdater->method('updateLocal')->willReturn(new ProductCollection());
        $this->remoteUpdater->method('updateRemote')->willReturn(new ProductCollection());

        $this->inventoryRepository->expects(static::never())->method('upsert');

        $this->inventorySyncer->sync(
            new ProductCollection([$product]),
            $this->inventoryContext
        );
    }
}
