<?php declare(strict_types=1);
/*
 * (c) shopware AG <info@shopware.com>
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Swag\PayPal\Test\IZettle\Sync\Inventory;

use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Shopware\Core\Content\Product\ProductCollection;
use Swag\PayPal\IZettle\Api\Inventory\Changes;
use Swag\PayPal\IZettle\Api\Inventory\Changes\Change;
use Swag\PayPal\IZettle\Api\Inventory\Status;
use Swag\PayPal\IZettle\Api\Inventory\Status\Variant;
use Swag\PayPal\IZettle\Api\Service\Converter\UuidConverter;
use Swag\PayPal\IZettle\Api\Service\Inventory\RemoteCalculator;
use Swag\PayPal\IZettle\Resource\InventoryResource;
use Swag\PayPal\IZettle\Sync\Inventory\RemoteUpdater;

class RemoteUpdaterTest extends TestCase
{
    use UpdaterTrait;

    /**
     * @var MockObject
     */
    private $inventoryResource;

    /**
     * @var RemoteUpdater
     */
    private $remoteUpdater;

    public function setUp(): void
    {
        $this->inventoryResource = $this->createMock(InventoryResource::class);

        $remoteCalculator = new RemoteCalculator(new UuidConverter());

        $this->remoteUpdater = new RemoteUpdater($this->inventoryResource, $remoteCalculator);
    }

    /**
     * @dataProvider dataProviderInventoryUpdate
     */
    public function testUpdateRemoteInventoryVariant(int $localInventory, int $newLocalInventory, int $change): void
    {
        $product = $this->getVariantProduct();
        $product->setAvailableStock($newLocalInventory);

        $inventoryContext = $this->createInventoryContext($product, $localInventory, 0);

        $uuidConverter = new UuidConverter();

        $changes = new Changes();
        $changeObject = new Change();
        $changeObject->setProductUuid($uuidConverter->convertUuidToV1((string) $product->getParentId()));
        $changeObject->setVariantUuid($uuidConverter->convertUuidToV1($product->getId()));
        $changeObject->setFromLocationUuid($change > 0 ? $this->locations['SUPPLIER'] : $this->locations['STORE']);
        $changeObject->setToLocationUuid($change < 0 ? $this->locations['BIN'] : $this->locations['STORE']);
        $changeObject->setChange(\abs($change));
        $changes->addChange($changeObject);
        $changes->setReturnBalanceForLocationUuid($this->locations['STORE']);

        $this->inventoryResource->expects($change === 0 ? static::never() : static::once())
                                ->method('changeInventory')
                                ->with(static::anything(), $changes)
                                ->willReturn($this->createStatus($changeObject->getProductUuid(), $changeObject->getVariantUuid()));

        $this->remoteUpdater->updateRemote(new ProductCollection([$product]), $inventoryContext);
    }

    /**
     * @dataProvider dataProviderInventoryUpdate
     */
    public function testUpdateRemoteInventorySingle(int $localInventory, int $newLocalInventory, int $change): void
    {
        $product = $this->getSingleProduct();
        $product->setAvailableStock($newLocalInventory);

        $inventoryContext = $this->createInventoryContext($product, $localInventory, 0);

        $uuidConverter = new UuidConverter();

        $changes = new Changes();
        $changeObject = new Change();
        $changeObject->setProductUuid($uuidConverter->convertUuidToV1($product->getId()));
        $changeObject->setVariantUuid($uuidConverter->convertUuidToV1($uuidConverter->incrementUuid($product->getId())));
        $changeObject->setFromLocationUuid($change > 0 ? $this->locations['SUPPLIER'] : $this->locations['STORE']);
        $changeObject->setToLocationUuid($change < 0 ? $this->locations['BIN'] : $this->locations['STORE']);
        $changeObject->setChange(\abs($change));
        $changes->addChange($changeObject);
        $changes->setReturnBalanceForLocationUuid($this->locations['STORE']);

        $this->inventoryResource->expects($change === 0 ? static::never() : static::once())
                                ->method('changeInventory')
                                ->with(static::anything(), $changes);

        $this->remoteUpdater->updateRemote(new ProductCollection([$product]), $inventoryContext);
    }

    public function testUpdateRemoteInventoryWithParentProduct(): void
    {
        $product = $this->getParentProduct();

        $inventoryContext = $this->createInventoryContext($product, 5, 0);

        $this->inventoryResource->expects(static::never())->method('changeInventory');

        $this->remoteUpdater->updateRemote(new ProductCollection([$product]), $inventoryContext);
    }

    private function createStatus(string $productUuid, string $variantUuid): Status
    {
        $status = new Status();
        $variant = new Variant();
        $variant->assign([
            'productUuid' => $productUuid,
            'variantUuid' => $variantUuid,
            'balance' => '5',
        ]);
        $status->addVariant($variant);

        return $status;
    }
}
