<?php declare(strict_types=1);
/*
 * (c) shopware AG <info@shopware.com>
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Swag\PayPal\Test\IZettle\Sync\Inventory;

use PHPUnit\Framework\TestCase;
use Shopware\Core\Defaults;
use Shopware\Core\Framework\Context;
use Shopware\Core\Framework\Test\TestCaseBase\KernelTestBehaviour;
use Swag\PayPal\IZettle\Api\Inventory\Location;
use Swag\PayPal\IZettle\Api\Inventory\Status;
use Swag\PayPal\IZettle\Api\Inventory\Status\Variant;
use Swag\PayPal\IZettle\Api\Service\Converter\UuidConverter;
use Swag\PayPal\IZettle\DataAbstractionLayer\Entity\IZettleSalesChannelEntity;
use Swag\PayPal\IZettle\Resource\InventoryResource;
use Swag\PayPal\IZettle\Sync\Context\InventoryContextFactory;
use Swag\PayPal\Test\Mock\IZettle\IZettleInventoryRepoMock;

class InventoryContextFactoryTest extends TestCase
{
    use KernelTestBehaviour;
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
     * @var \PHPUnit\Framework\MockObject\MockObject
     */
    private $inventoryResource;

    /**
     * @var IZettleSalesChannelEntity
     */
    private $salesChannel;

    public function setUp(): void
    {
        $this->salesChannel = $this->getIZettleSalesChannel();

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
        $status->setTrackedProducts([$uuidConverter->convertUuidToV1((string) $product->getParentId())]);

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
        $status->setTrackedProducts([$uuidConverter->convertUuidToV1($product->getId())]);

        $this->inventoryResource->method('getInventory')->willReturn($status);
        $this->inventoryResource->expects(static::never())->method('startTracking');

        $inventoryContext = $this->inventoryContextFactory->getContext($this->salesChannel, $context);

        static::assertEquals($product->getAvailableStock(), $inventoryContext->getIZettleInventory($product));
    }

    public function testIZettleInventoryUntrackedWithTrackingReturn(): void
    {
        $context = Context::createDefaultContext();

        $uuidConverter = new UuidConverter();
        $status = new Status();
        $product = $this->getVariantProduct();
        $status->setTrackedProducts([]);

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

        static::assertEquals($product->getAvailableStock(), $inventoryContext->getIZettleInventory($product));
    }

    public function testIZettleInventoryUntrackedWithTrackingReturnAndExistingInventory(): void
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
        $status->setTrackedProducts([]);

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

        static::assertEquals($product->getAvailableStock() + 1, $inventoryContext->getIZettleInventory($product));
    }

    public function testIZettleInventoryUntrackedWithoutTrackingReturn(): void
    {
        $context = Context::createDefaultContext();

        $status = new Status();
        $product = $this->getVariantProduct();
        $status->setTrackedProducts([]);

        $this->inventoryResource->method('getInventory')->willReturn($status);
        $this->inventoryResource->expects(static::once())->method('startTracking')->willReturn(null);

        $inventoryContext = $this->inventoryContextFactory->getContext($this->salesChannel, $context);

        static::assertEquals(0, $inventoryContext->getIZettleInventory($product));
    }

    public function testIZettleInventoryUntrackedWithEmptyTrackingReturn(): void
    {
        $context = Context::createDefaultContext();

        $status = new Status();
        $product = $this->getVariantProduct();
        $status->setTrackedProducts([]);

        $newStatus = new Status();

        $this->inventoryResource->method('getInventory')->willReturn($status);
        $this->inventoryResource->expects(static::once())->method('startTracking')->willReturn($newStatus);

        $inventoryContext = $this->inventoryContextFactory->getContext($this->salesChannel, $context);

        static::assertEquals(0, $inventoryContext->getIZettleInventory($product));
    }

    public function testLocalInventory(): void
    {
        $context = Context::createDefaultContext();

        $singleProduct = $this->getSingleProduct();
        $this->inventoryRepository->addMockEntity($singleProduct, Defaults::SALES_CHANNEL, (int) $singleProduct->getAvailableStock());
        $variantProduct = $this->getVariantProduct();
        $this->inventoryRepository->addMockEntity($variantProduct, Defaults::SALES_CHANNEL, $variantProduct->getAvailableStock() + 2);

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

        $this->inventoryRepository->addMockEntity($singleProduct, Defaults::SALES_CHANNEL, (int) $singleProduct->getAvailableStock());
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
