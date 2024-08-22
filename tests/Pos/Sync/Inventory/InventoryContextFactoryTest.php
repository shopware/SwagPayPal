<?php declare(strict_types=1);
/*
 * (c) shopware AG <info@shopware.com>
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Swag\PayPal\Test\Pos\Sync\Inventory;

use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Shopware\Core\Framework\Context;
use Shopware\Core\Framework\Log\Package;
use Shopware\Core\System\SalesChannel\SalesChannelEntity;
use Shopware\Core\Test\TestDefaults;
use Swag\PayPal\Pos\Api\Inventory\Location;
use Swag\PayPal\Pos\Api\Inventory\Status;
use Swag\PayPal\Pos\Api\Inventory\Status\Variant;
use Swag\PayPal\Pos\Api\Service\Converter\UuidConverter;
use Swag\PayPal\Pos\Resource\InventoryResource;
use Swag\PayPal\Pos\Sync\Context\InventoryContextFactory;
use Swag\PayPal\Test\Pos\Mock\Repositories\PosInventoryRepoMock;

/**
 * @internal
 */
#[Package('checkout')]
class InventoryContextFactoryTest extends TestCase
{
    use InventoryTrait;

    private InventoryContextFactory $inventoryContextFactory;

    private PosInventoryRepoMock $inventoryRepository;

    private MockObject $inventoryResource;

    private SalesChannelEntity $salesChannel;

    protected function setUp(): void
    {
        $context = Context::createDefaultContext();

        $this->salesChannel = $this->getSalesChannel($context);

        $this->inventoryRepository = new PosInventoryRepoMock();

        $this->inventoryResource = $this->createMock(InventoryResource::class);
        $this->inventoryResource->method('getLocations')->willReturn($this->getPosLocations());

        $this->inventoryContextFactory = new InventoryContextFactory(
            $this->inventoryResource,
            new UuidConverter(),
            $this->inventoryRepository
        );
    }

    public function testLocations(): void
    {
        $inventoryContext = $this->inventoryContextFactory->getContext($this->salesChannel);

        static::assertSame($this->locations['STORE'], $inventoryContext->getStoreUuid());
        static::assertSame($this->locations['BIN'], $inventoryContext->getBinUuid());
        static::assertSame($this->locations['SUPPLIER'], $inventoryContext->getSupplierUuid());
        static::assertSame($this->locations['SOLD'], $inventoryContext->getSoldUuid());
    }

    public function testPosInventoryVariant(): void
    {
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

        $inventoryContext = $this->inventoryContextFactory->getContext($this->salesChannel);

        static::assertSame($product->getAvailableStock(), $inventoryContext->getSingleRemoteInventory($product));
    }

    public function testPosInventorySingle(): void
    {
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

        $inventoryContext = $this->inventoryContextFactory->getContext($this->salesChannel);

        static::assertSame($product->getAvailableStock(), $inventoryContext->getSingleRemoteInventory($product));
    }

    public function testPosInventoryUntracked(): void
    {
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

        $inventoryContext = $this->inventoryContextFactory->getContext($this->salesChannel);

        static::assertNull($inventoryContext->getSingleRemoteInventory($product));
    }

    public function testLocalInventory(): void
    {
        $singleProduct = $this->getSingleProduct();
        $this->inventoryRepository->createMockEntity($singleProduct, TestDefaults::SALES_CHANNEL, (int) $singleProduct->getAvailableStock());
        $variantProduct = $this->getVariantProduct();
        $this->inventoryRepository->createMockEntity($variantProduct, TestDefaults::SALES_CHANNEL, $variantProduct->getAvailableStock() + 2);

        $inventoryContext = $this->inventoryContextFactory->getContext($this->salesChannel);
        $this->inventoryContextFactory->updateLocal($inventoryContext);

        static::assertSame($singleProduct->getAvailableStock(), $inventoryContext->getLocalInventory($singleProduct));
        static::assertSame($variantProduct->getAvailableStock() + 2, $inventoryContext->getLocalInventory($variantProduct));
    }

    public function testLocalInventoryEmpty(): void
    {
        $singleProduct = $this->getSingleProduct();

        $inventoryContext = $this->inventoryContextFactory->getContext($this->salesChannel);

        static::assertNull($inventoryContext->getLocalInventory($singleProduct));
    }

    public function testLocalInventoryRefresh(): void
    {
        $singleProduct = $this->getSingleProduct();
        $inventoryContext = $this->inventoryContextFactory->getContext($this->salesChannel);
        static::assertNull($inventoryContext->getLocalInventory($singleProduct));

        $this->inventoryRepository->createMockEntity($singleProduct, TestDefaults::SALES_CHANNEL, (int) $singleProduct->getAvailableStock());
        $this->inventoryContextFactory->updateLocal($inventoryContext);
        static::assertSame($singleProduct->getAvailableStock(), $inventoryContext->getLocalInventory($singleProduct));
    }

    private function getPosLocations(): array
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
