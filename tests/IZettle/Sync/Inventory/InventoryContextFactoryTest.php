<?php declare(strict_types=1);
/*
 * (c) shopware AG <info@shopware.com>
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Swag\PayPal\Test\IZettle\Sync\Inventory;

use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Shopware\Core\Defaults;
use Shopware\Core\Framework\Context;
use Shopware\Core\System\SalesChannel\SalesChannelEntity;
use Swag\PayPal\IZettle\Api\Inventory\Location;
use Swag\PayPal\IZettle\Api\Inventory\Status;
use Swag\PayPal\IZettle\Api\Inventory\Status\Variant;
use Swag\PayPal\IZettle\Api\Service\Converter\UuidConverter;
use Swag\PayPal\IZettle\Resource\InventoryResource;
use Swag\PayPal\IZettle\Sync\Context\InventoryContextFactory;
use Swag\PayPal\Test\IZettle\Mock\Repositories\IZettleInventoryRepoMock;

class InventoryContextFactoryTest extends TestCase
{
    use InventoryTrait;

    /**
     * @var InventoryContextFactory
     */
    private $inventoryContextFactory;

    /**
     * @var IZettleInventoryRepoMock
     */
    private $inventoryRepository;

    /**
     * @var MockObject
     */
    private $inventoryResource;

    /**
     * @var SalesChannelEntity
     */
    private $salesChannel;

    public function setUp(): void
    {
        $context = Context::createDefaultContext();

        $this->salesChannel = $this->createSalesChannel($context);

        $this->inventoryRepository = new IZettleInventoryRepoMock();

        $this->inventoryResource = $this->createMock(InventoryResource::class);
        $this->inventoryResource->method('getLocations')->willReturn($this->getIZettleLocations());

        $this->inventoryContextFactory = new InventoryContextFactory(
            $this->inventoryResource,
            new UuidConverter(),
            $this->inventoryRepository
        );
    }

    public function testLocations(): void
    {
        $context = Context::createDefaultContext();

        $inventoryContext = $this->inventoryContextFactory->getContext($this->salesChannel, $context);

        static::assertEquals($this->locations['STORE'], $inventoryContext->getStoreUuid());
        static::assertEquals($this->locations['BIN'], $inventoryContext->getBinUuid());
        static::assertEquals($this->locations['SUPPLIER'], $inventoryContext->getSupplierUuid());
        static::assertEquals($this->locations['SOLD'], $inventoryContext->getSoldUuid());
    }

    public function testIdentical(): void
    {
        $context = Context::createDefaultContext();

        $inventoryContextFirst = $this->inventoryContextFactory->getContext($this->salesChannel, $context);
        $inventoryContextSecond = $this->inventoryContextFactory->getContext($this->salesChannel, $context);

        static::assertSame($inventoryContextFirst, $inventoryContextSecond);
    }

    public function testIZettleInventoryVariant(): void
    {
        $context = Context::createDefaultContext();

        $uuidConverter = new UuidConverter();
        $status = new Status();
        $product = $this->getVariantProduct();
        $variant = new Variant();
        $variant->assign([
            'productUuid' => $uuidConverter->convertUuidToV1((string) $product->getParentId()),
            'variantUuid' => $uuidConverter->convertUuidToV1($product->getId()),
            'balance' => (string) $product->getAvailableStock(),
        ]);
        $status->addVariant($variant);
        $status->assign(['trackedProducts' => [$uuidConverter->convertUuidToV1((string) $product->getParentId())]]);

        $this->inventoryResource->method('getInventory')->willReturn($status);
        $this->inventoryResource->expects(static::never())->method('startTracking');

        $inventoryContext = $this->inventoryContextFactory->getContext($this->salesChannel, $context);

        static::assertEquals($product->getAvailableStock(), $inventoryContext->getIZettleInventory($product));
    }

    public function testIZettleInventorySingle(): void
    {
        $context = Context::createDefaultContext();

        $uuidConverter = new UuidConverter();
        $status = new Status();
        $product = $this->getSingleProduct();
        $variant = new Variant();
        $variant->assign([
            'productUuid' => $uuidConverter->convertUuidToV1($product->getId()),
            'variantUuid' => $uuidConverter->convertUuidToV1($uuidConverter->incrementUuid($product->getId())),
            'balance' => (string) $product->getAvailableStock(),
        ]);
        $status->addVariant($variant);
        $status->assign(['trackedProducts' => [$uuidConverter->convertUuidToV1($product->getId())]]);

        $this->inventoryResource->method('getInventory')->willReturn($status);
        $this->inventoryResource->expects(static::never())->method('startTracking');

        $inventoryContext = $this->inventoryContextFactory->getContext($this->salesChannel, $context);

        static::assertEquals($product->getAvailableStock(), $inventoryContext->getIZettleInventory($product));
    }

    public function testIZettleInventoryUntracked(): void
    {
        $context = Context::createDefaultContext();

        $uuidConverter = new UuidConverter();
        $status = new Status();
        $product = $this->getVariantProduct();
        $status->assign(['trackedProducts' => [], 'variants' => [
            [
                'productUuid' => $uuidConverter->convertUuidToV1((string) $product->getParentId()),
                'variantUuid' => $uuidConverter->convertUuidToV1($product->getId()),
                'balance' => (string) $product->getAvailableStock(),
            ],
        ]]);

        $this->inventoryResource->method('getInventory')->willReturn($status);

        $inventoryContext = $this->inventoryContextFactory->getContext($this->salesChannel, $context);

        static::assertNull($inventoryContext->getIZettleInventory($product));
    }

    public function testStartIZettleInventoryTrackingWithTrackingReturn(): void
    {
        $context = Context::createDefaultContext();

        $uuidConverter = new UuidConverter();
        $status = new Status();
        $product = $this->getVariantProduct();
        $status->assign(['trackedProducts' => []]);

        $newStatus = new Status();
        $variant = new Variant();
        $variant->assign([
            'productUuid' => $uuidConverter->convertUuidToV1((string) $product->getParentId()),
            'variantUuid' => $uuidConverter->convertUuidToV1($product->getId()),
            'balance' => (string) $product->getAvailableStock(),
        ]);
        $newStatus->addVariant($variant);

        $this->inventoryResource->method('getInventory')->willReturn($status);
        $this->inventoryResource->expects(static::once())->method('startTracking')->willReturn($newStatus);

        $inventoryContext = $this->inventoryContextFactory->getContext($this->salesChannel, $context);

        $inventoryContext->startIZettleTracking($product);

        static::assertEquals($product->getAvailableStock(), $inventoryContext->getIZettleInventory($product, true));
    }

    public function testStartIZettleInventoryTrackingWithTrackingReturnAndExistingInventory(): void
    {
        $context = Context::createDefaultContext();

        $uuidConverter = new UuidConverter();
        $status = new Status();
        $product = $this->getVariantProduct();
        $variant = new Variant();
        $variant->assign([
            'productUuid' => $uuidConverter->convertUuidToV1((string) $product->getParentId()),
            'variantUuid' => $uuidConverter->convertUuidToV1($product->getId()),
            'balance' => (string) $product->getAvailableStock(),
        ]);
        $status->addVariant($variant);
        $status->assign(['trackedProducts' => []]);

        $newStatus = new Status();
        $variant = new Variant();
        $variant->assign([
            'productUuid' => $uuidConverter->convertUuidToV1((string) $product->getParentId()),
            'variantUuid' => $uuidConverter->convertUuidToV1($product->getId()),
            'balance' => (string) ($product->getAvailableStock() + 1),
        ]);
        $newStatus->addVariant($variant);

        $this->inventoryResource->method('getInventory')->willReturn($status);
        $this->inventoryResource->expects(static::once())->method('startTracking')->willReturn($newStatus);

        $inventoryContext = $this->inventoryContextFactory->getContext($this->salesChannel, $context);

        $inventoryContext->startIZettleTracking($product);

        static::assertEquals($product->getAvailableStock() + 1, $inventoryContext->getIZettleInventory($product, true));
    }

    public function testStartIZettleInventoryTrackingWithoutTrackingReturn(): void
    {
        $context = Context::createDefaultContext();

        $status = new Status();
        $product = $this->getVariantProduct();
        $status->assign(['trackedProducts' => []]);

        $this->inventoryResource->method('getInventory')->willReturn($status);
        $this->inventoryResource->expects(static::once())->method('startTracking')->willReturn(null);

        $inventoryContext = $this->inventoryContextFactory->getContext($this->salesChannel, $context);

        $inventoryContext->startIZettleTracking($product);

        static::assertNull($inventoryContext->getIZettleInventory($product, true));
    }

    public function testStartIZettleInventoryTrackingRepeatedly(): void
    {
        $context = Context::createDefaultContext();

        $status = new Status();
        $product = $this->getVariantProduct();
        $status->assign(['trackedProducts' => []]);

        $this->inventoryResource->method('getInventory')->willReturn($status);
        $this->inventoryResource->expects(static::once())->method('startTracking')->willReturn(null);

        $inventoryContext = $this->inventoryContextFactory->getContext($this->salesChannel, $context);

        $inventoryContext->startIZettleTracking($product);
        $inventoryContext->startIZettleTracking($product);

        static::assertNull($inventoryContext->getIZettleInventory($product, true));
    }

    public function testIZettleInventoryUntrackedWithEmptyTrackingReturn(): void
    {
        $context = Context::createDefaultContext();

        $status = new Status();
        $product = $this->getVariantProduct();
        $status->assign(['trackedProducts' => []]);

        $newStatus = new Status();

        $this->inventoryResource->method('getInventory')->willReturn($status);
        $this->inventoryResource->expects(static::once())->method('startTracking')->willReturn($newStatus);

        $inventoryContext = $this->inventoryContextFactory->getContext($this->salesChannel, $context);

        $inventoryContext->startIZettleTracking($product);

        static::assertNull($inventoryContext->getIZettleInventory($product, true));
    }

    public function testLocalInventory(): void
    {
        $context = Context::createDefaultContext();

        $singleProduct = $this->getSingleProduct();
        $this->inventoryRepository->createMockEntity($singleProduct, Defaults::SALES_CHANNEL, (int) $singleProduct->getAvailableStock());
        $variantProduct = $this->getVariantProduct();
        $this->inventoryRepository->createMockEntity($variantProduct, Defaults::SALES_CHANNEL, $variantProduct->getAvailableStock() + 2);

        $inventoryContext = $this->inventoryContextFactory->getContext($this->salesChannel, $context);

        static::assertEquals($singleProduct->getAvailableStock(), $inventoryContext->getLocalInventory($singleProduct));
        static::assertEquals($variantProduct->getAvailableStock() + 2, $inventoryContext->getLocalInventory($variantProduct));
    }

    public function testLocalInventoryEmpty(): void
    {
        $context = Context::createDefaultContext();

        $singleProduct = $this->getSingleProduct();

        $inventoryContext = $this->inventoryContextFactory->getContext($this->salesChannel, $context);

        static::assertEquals(0, $inventoryContext->getLocalInventory($singleProduct));
    }

    public function testLocalInventoryRefresh(): void
    {
        $context = Context::createDefaultContext();

        $singleProduct = $this->getSingleProduct();
        $inventoryContext = $this->inventoryContextFactory->getContext($this->salesChannel, $context);
        static::assertEquals(0, $inventoryContext->getLocalInventory($singleProduct));

        $this->inventoryRepository->createMockEntity($singleProduct, Defaults::SALES_CHANNEL, (int) $singleProduct->getAvailableStock());
        $this->inventoryContextFactory->updateContext($inventoryContext);
        static::assertEquals($singleProduct->getAvailableStock(), $inventoryContext->getLocalInventory($singleProduct));
    }

    private function getIZettleLocations(): array
    {
        $locations = [];
        foreach ($this->locations as $name => $uuid) {
            $location = new Location();
            $location->assign([
                'name' => $name,
                'type' => $name,
                'uuid' => $uuid,
            ]);
            $locations[] = $location;
        }

        return $locations;
    }
}
