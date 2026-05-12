<?php declare(strict_types=1);
/*
 * (c) shopware AG <info@shopware.com>
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Swag\PayPal\Pos\Sync\Inventory;

use Psr\Log\LoggerInterface;
use Shopware\Core\Content\Product\ProductCollection;
use Shopware\Core\Framework\DataAbstractionLayer\EntityRepository;
use Shopware\Core\Framework\Log\Package;
use Swag\PayPal\Pos\Sync\Context\InventoryContext;
use Swag\PayPal\Pos\Sync\Inventory\Calculator\LocalCalculatorInterface;

#[Package('checkout')]
class LocalUpdater
{
    /**
     * @internal
     */
    public function __construct(
        private readonly EntityRepository $productRepository,
        private readonly LocalCalculatorInterface $localCalculator,
        private readonly LoggerInterface $logger,
    ) {
    }

    public function updateLocal(ProductCollection $productCollection, InventoryContext $inventoryContext): ProductCollection
    {
        $productChanges = [];
        $changedProducts = new ProductCollection();
        foreach ($productCollection->getElements() as $productEntity) {
            if ($productEntity->getChildCount() > 0) {
                continue;
            }

            $stockChange = $this->localCalculator->getChangeAmount($productEntity, $inventoryContext);

            if ($stockChange === 0 || $inventoryContext->getSingleRemoteInventory($productEntity) === null) {
                continue;
            }

            $productEntity->addExtension(StockChange::STOCK_CHANGE_EXTENSION, new StockChange($stockChange));
            $changedProducts->add($productEntity);
            $productChanges[] = [
                'id' => $productEntity->getId(),
                'versionId' => $productEntity->getVersionId(),
                'stock' => $productEntity->getStock() + $stockChange,
            ];
        }

        if (\count($changedProducts) === 0) {
            return $changedProducts;
        }

        $this->productRepository->update($productChanges, $inventoryContext->getContext());

        foreach ($changedProducts as $changedProduct) {
            /** @var StockChange|null $stockChange */
            $stockChange = $changedProduct->getExtension(StockChange::STOCK_CHANGE_EXTENSION);

            if ($stockChange === null) {
                continue;
            }

            $this->logger->info('Changed local inventory of {productName} by {change}', [
                'product' => $changedProduct,
                'productName' => $changedProduct->getName() ?? 'variant',
                'change' => $stockChange->getStockChange(),
            ]);
        }

        return $changedProducts;
    }
}
