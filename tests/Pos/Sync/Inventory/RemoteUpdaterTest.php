<?php declare(strict_types=1);
/*
 * (c) shopware AG <info@shopware.com>
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Swag\PayPal\Test\Pos\Sync\Inventory;

use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Psr\Log\LoggerInterface;
use Shopware\Core\Content\Product\ProductCollection;
use Shopware\Core\Framework\Log\Package;
use Swag\PayPal\Pos\Api\Error\PosApiError;
use Swag\PayPal\Pos\Api\Exception\PosApiException;
use Swag\PayPal\Pos\Api\Inventory\BulkChanges;
use Swag\PayPal\Pos\Api\Inventory\BulkChanges\ProductChange;
use Swag\PayPal\Pos\Api\Inventory\BulkChanges\ProductChange\VariantChange;
use Swag\PayPal\Pos\Api\Inventory\Status;
use Swag\PayPal\Pos\Api\Inventory\Status\Variant;
use Swag\PayPal\Pos\Api\Service\Converter\UuidConverter;
use Swag\PayPal\Pos\Resource\InventoryResource;
use Swag\PayPal\Pos\Sync\Inventory\Calculator\RemoteCalculator;
use Swag\PayPal\Pos\Sync\Inventory\RemoteUpdater;

/**
 * @internal
 */
#[Package('checkout')]
class RemoteUpdaterTest extends TestCase
{
    use UpdaterTrait;

    private MockObject $inventoryResource;

    private MockObject $logger;

    private RemoteUpdater $remoteUpdater;

    protected function setUp(): void
    {
        $this->inventoryResource = $this->createMock(InventoryResource::class);

        $remoteCalculator = new RemoteCalculator(new UuidConverter());

        $this->logger = $this->createMock(LoggerInterface::class);

        $this->remoteUpdater = new RemoteUpdater($this->inventoryResource, $remoteCalculator, $this->logger);
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

        $bulkChanges = new BulkChanges();
        $productChange = new ProductChange();
        $variantChange = new VariantChange();
        $variantChange->setProductUuid($uuidConverter->convertUuidToV1((string) $product->getParentId()));
        $variantChange->setVariantUuid($uuidConverter->convertUuidToV1($product->getId()));
        $variantChange->setFromLocationUuid($change > 0 ? $this->locations['SUPPLIER'] : $this->locations['STORE']);
        $variantChange->setToLocationUuid($change < 0 ? $this->locations['BIN'] : $this->locations['STORE']);
        $variantChange->setChange(\abs($change));
        $productChange->addVariantChange($variantChange);
        $productChange->setProductUuid($variantChange->getProductUuid());
        $productChange->setTrackingStatusChange(ProductChange::TRACKING_NOCHANGE);
        $bulkChanges->addProductChange($productChange);
        $bulkChanges->setReturnBalanceForLocationUuid($this->locations['STORE']);

        $this->inventoryResource->expects($change === 0 ? static::never() : static::once())
                                ->method('changeInventoryBulk')
                                ->with(static::anything(), $bulkChanges)
                                ->willReturn($this->createStatus($variantChange->getProductUuid(), $variantChange->getVariantUuid()));

        $this->logger->expects($change === 0 ? static::never() : static::once())
                     ->method('info');

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

        $bulkChanges = new BulkChanges();
        $productChange = new ProductChange();
        $variantChange = new VariantChange();
        $variantChange->setProductUuid($uuidConverter->convertUuidToV1($product->getId()));
        $variantChange->setVariantUuid($uuidConverter->convertUuidToV1($uuidConverter->incrementUuid($product->getId())));
        $variantChange->setFromLocationUuid($change > 0 ? $this->locations['SUPPLIER'] : $this->locations['STORE']);
        $variantChange->setToLocationUuid($change < 0 ? $this->locations['BIN'] : $this->locations['STORE']);
        $variantChange->setChange(\abs($change));
        $productChange->addVariantChange($variantChange);
        $productChange->setProductUuid($variantChange->getProductUuid());
        $productChange->setTrackingStatusChange(ProductChange::TRACKING_NOCHANGE);
        $bulkChanges->addProductChange($productChange);
        $bulkChanges->setReturnBalanceForLocationUuid($this->locations['STORE']);

        $this->inventoryResource->expects($change === 0 ? static::never() : static::once())
                                ->method('changeInventoryBulk')
                                ->with(static::anything(), $bulkChanges);

        $this->remoteUpdater->updateRemote(new ProductCollection([$product]), $inventoryContext);
    }

    public function testUpdateRemoteInventoryWithParentProduct(): void
    {
        $product = $this->getParentProduct();

        $inventoryContext = $this->createInventoryContext($product, 5, 0);

        $this->inventoryResource->expects(static::never())->method('changeInventoryBulk');

        $this->remoteUpdater->updateRemote(new ProductCollection([$product]), $inventoryContext);
    }

    public function testUpdateRemoteInventoryWithError(): void
    {
        $product = $this->getSingleProduct();
        $product->setAvailableStock(2);

        $inventoryContext = $this->createInventoryContext($product, 1, 0);

        $error = new PosApiError();
        $error->assign([
            'developerMessage' => 'anyError',
            'violations' => [], ]);
        $this->inventoryResource->method('changeInventoryBulk')->willThrowException(
            new PosApiException($error)
        );

        $this->logger->expects(static::once())->method('error');
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
