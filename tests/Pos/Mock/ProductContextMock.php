<?php declare(strict_types=1);
/*
 * (c) shopware AG <info@shopware.com>
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Swag\PayPal\Test\Pos\Mock;

use Shopware\Core\Content\Product\ProductEntity;
use Shopware\Core\Framework\Context;
use Shopware\Core\System\SalesChannel\SalesChannelEntity;
use Swag\PayPal\Pos\Api\Product;
use Swag\PayPal\Pos\DataAbstractionLayer\Entity\PosSalesChannelMediaCollection;
use Swag\PayPal\Pos\DataAbstractionLayer\Entity\PosSalesChannelProductCollection;
use Swag\PayPal\Pos\DataAbstractionLayer\Entity\PosSalesChannelProductEntity;
use Swag\PayPal\Pos\Sync\Context\ProductContext;

class ProductContextMock extends ProductContext
{
    private int $status;

    public function __construct(
        SalesChannelEntity $salesChannelEntity,
        Context $context,
        ?PosSalesChannelProductEntity $posProductEntity = null
    ) {
        $posProductCollection = new PosSalesChannelProductCollection();
        if ($posProductEntity !== null) {
            $posProductCollection->add($posProductEntity);
        }

        parent::__construct(
            $salesChannelEntity,
            $posProductCollection,
            new PosSalesChannelMediaCollection(),
            $context
        );
    }

    public function checkForUpdate(ProductEntity $shopwareProduct, Product $posProduct): int
    {
        return $this->status;
    }

    public function setUpdateStatus(int $status): void
    {
        $this->status = $status;
    }
}
