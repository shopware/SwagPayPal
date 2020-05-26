<?php declare(strict_types=1);
/*
 * (c) shopware AG <info@shopware.com>
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Swag\PayPal\IZettle\Sync\Inventory;

use Psr\Log\LoggerInterface;
use Shopware\Core\Content\Product\ProductCollection;
use Swag\PayPal\IZettle\Api\Exception\IZettleApiException;
use Swag\PayPal\IZettle\Api\Inventory\Changes;
use Swag\PayPal\IZettle\Api\Service\Inventory\RemoteCalculator;
use Swag\PayPal\IZettle\Resource\InventoryResource;
use Swag\PayPal\IZettle\Sync\Context\InventoryContext;

class RemoteUpdater
{
    /**
     * @var InventoryResource
     */
    private $inventoryResource;

    /**
     * @var RemoteCalculator
     */
    private $remoteCalculator;

    /**
     * @var LoggerInterface
     */
    private $logger;

    public function __construct(
        InventoryResource $inventoryResource,
        RemoteCalculator $remoteCalculator,
        LoggerInterface $logger
    ) {
        $this->inventoryResource = $inventoryResource;
        $this->remoteCalculator = $remoteCalculator;
        $this->logger = $logger;
    }

    public function updateRemote(ProductCollection $productCollection, InventoryContext $inventoryContext): ProductCollection
    {
        $iZettleChanges = new Changes();
        $changedProducts = new ProductCollection();
        foreach ($productCollection->getElements() as $productEntity) {
            if ($productEntity->getChildCount() > 0) {
                continue;
            }

            $iZettleChange = $this->remoteCalculator->calculateRemoteChange($productEntity, $inventoryContext);
            if ($iZettleChange === null) {
                continue;
            }

            $iZettleChanges->addChange($iZettleChange);

            $changedProducts->add($productEntity);
        }

        if (\count($iZettleChanges->getChanges()) === 0) {
            return $changedProducts;
        }

        $iZettleChanges->setReturnBalanceForLocationUuid($inventoryContext->getStoreUuid());

        try {
            $status = $this->inventoryResource->changeInventory($inventoryContext->getIZettleSalesChannel(), $iZettleChanges);
        } catch (IZettleApiException $iZettleApiException) {
            $this->logger->error('Inventory sync error: ' . $iZettleApiException);

            return $changedProducts;
        }

        foreach ($changedProducts as $changedProduct) {
            $changeAmount = $this->remoteCalculator->getChangeAmount($changedProduct, $inventoryContext);
            $this->logger->info('Changed remote inventory of {productName} by {change}', [
                'product' => $changedProduct,
                'productName' => $changedProduct->getName() ?? 'variant',
                'change' => $changeAmount,
            ]);
        }

        if ($status === null || \count($status->getVariants()) === 0) {
            return $changedProducts;
        }

        foreach ($status->getVariants() as $variant) {
            $inventoryContext->addIZettleInventory($variant);
        }

        return $changedProducts;
    }
}
