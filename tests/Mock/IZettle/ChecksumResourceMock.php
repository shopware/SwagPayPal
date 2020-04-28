<?php declare(strict_types=1);
/*
 * (c) shopware AG <info@shopware.com>
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Swag\PayPal\Test\Mock\IZettle;

use Shopware\Core\Content\Product\ProductEntity;
use Shopware\Core\Framework\Context;
use Swag\PayPal\IZettle\Api\Product;
use Swag\PayPal\IZettle\Sync\ChecksumResource;

class ChecksumResourceMock extends ChecksumResource
{
    /**
     * @var string[]
     */
    private $outdatedIds;

    /**
     * @var string[]
     */
    private $currentIds;

    /**
     * @var int
     */
    private $status;

    public function __construct()
    {
        parent::__construct(new SalesChannelRepoMock());
        $this->outdatedIds = [];
        $this->currentIds = [];
        $this->status = 0;
    }

    public function begin(string $salesChannelId, Context $context): void
    {
        if ($this->status === 0) {
            $this->status = 1;
        }
    }

    public function commit(Context $context): void
    {
        if ($this->status === 1) {
            $this->status = 2;
        }
    }

    public function checkForUpdate(ProductEntity $shopwareProduct, Product $iZettleProduct): int
    {
        if (in_array($shopwareProduct->getId(), $this->currentIds, true)) {
            return self::PRODUCT_CURRENT;
        }

        if (in_array($shopwareProduct->getId(), $this->outdatedIds, true)) {
            return self::PRODUCT_OUTDATED;
        }

        return self::PRODUCT_NEW;
    }

    public function addOutdatedId(string $id): void
    {
        $this->outdatedIds[] = $id;
    }

    public function addCurrentId(string $id): void
    {
        $this->currentIds[] = $id;
    }

    public function getUpdatedProducts(): array
    {
        return $this->updatedProducts;
    }

    public function getRemovedProducts(): array
    {
        return $this->removedProducts;
    }

    public function getStatus(): int
    {
        return $this->status;
    }
}
