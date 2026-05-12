<?php declare(strict_types=1);
/*
 * (c) shopware AG <info@shopware.com>
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Swag\PayPal\Pos\Sync;

use Shopware\Core\Content\Product\ProductCollection;
use Shopware\Core\Framework\Context;
use Shopware\Core\Framework\DataAbstractionLayer\EntityCollection;
use Shopware\Core\Framework\Log\Package;
use Shopware\Core\System\SalesChannel\SalesChannelEntity;
use Swag\PayPal\Pos\Api\Service\ProductConverter;
use Swag\PayPal\Pos\Sync\Context\ProductContextFactory;
use Swag\PayPal\Pos\Sync\Product\DeletedUpdater;
use Swag\PayPal\Pos\Sync\Product\NewUpdater;
use Swag\PayPal\Pos\Sync\Product\OutdatedUpdater;
use Swag\PayPal\Pos\Sync\Product\UnsyncedChecker;

#[Package('checkout')]
class ProductSyncer
{
    private ProductConverter $productConverter;

    private ProductContextFactory $productContextFactory;

    private NewUpdater $newUpdater;

    private OutdatedUpdater $outdatedUpdater;

    private DeletedUpdater $deletedUpdater;

    private UnsyncedChecker $unsyncedChecker;

    /**
     * @internal
     */
    public function __construct(
        ProductConverter $productConverter,
        ProductContextFactory $productContextFactory,
        NewUpdater $newUpdater,
        OutdatedUpdater $outdatedUpdater,
        DeletedUpdater $deletedUpdater,
        UnsyncedChecker $unsyncedChecker,
    ) {
        $this->productConverter = $productConverter;
        $this->productContextFactory = $productContextFactory;
        $this->newUpdater = $newUpdater;
        $this->outdatedUpdater = $outdatedUpdater;
        $this->deletedUpdater = $deletedUpdater;
        $this->unsyncedChecker = $unsyncedChecker;
    }

    /**
     * @param ProductCollection $entityCollection
     */
    public function sync(
        EntityCollection $entityCollection,
        SalesChannelEntity $salesChannel,
        Context $context,
    ): void {
        $productContext = $this->productContextFactory->getContext($salesChannel, $context, $entityCollection);
        $currency = $productContext->getPosSalesChannel()->isSyncPrices() ? $salesChannel->getCurrency() : null;

        $productGroupings = $this->productConverter->convertShopwareProducts($entityCollection, $currency, $productContext);

        $this->newUpdater->update($productGroupings, $productContext);
        $this->productContextFactory->commit($productContext);

        $this->outdatedUpdater->update($productGroupings, $productContext);
        $this->productContextFactory->commit($productContext);
    }

    /**
     * @param string[] $productIds
     */
    public function cleanUp(
        array $productIds,
        SalesChannelEntity $salesChannel,
        Context $context,
    ): void {
        $productContext = $this->productContextFactory->getContext($salesChannel, $context);

        $this->unsyncedChecker->checkForUnsynced($productIds, $productContext);
        $this->deletedUpdater->update($productIds, $productContext);
        $this->productContextFactory->commit($productContext);
    }
}
