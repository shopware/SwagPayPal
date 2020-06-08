<?php declare(strict_types=1);
/*
 * (c) shopware AG <info@shopware.com>
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Swag\PayPal\Test\Mock\IZettle;

use Shopware\Core\Content\Product\ProductEntity;
use Shopware\Core\Framework\Context;
use Shopware\Core\System\SalesChannel\SalesChannelEntity;
use Swag\PayPal\IZettle\Api\Product;
use Swag\PayPal\IZettle\DataAbstractionLayer\Entity\IZettleSalesChannelMediaCollection;
use Swag\PayPal\IZettle\DataAbstractionLayer\Entity\IZettleSalesChannelProductCollection;
use Swag\PayPal\IZettle\DataAbstractionLayer\Entity\IZettleSalesChannelProductEntity;
use Swag\PayPal\IZettle\Sync\Context\ProductContext;

class ProductContextMock extends ProductContext
{
    /**
     * @var int
     */
    private $status;

    public function __construct(
        SalesChannelEntity $salesChannelEntity,
        Context $context,
        ?IZettleSalesChannelProductEntity $iZettleProductEntity = null
    ) {
        $iZettleProductCollection = new IZettleSalesChannelProductCollection();
        if ($iZettleProductEntity !== null) {
            $iZettleProductCollection->add($iZettleProductEntity);
        }

        parent::__construct(
            $salesChannelEntity,
            $iZettleProductCollection,
            new IZettleSalesChannelMediaCollection(),
            $context
        );
    }

    public function checkForUpdate(ProductEntity $shopwareProduct, Product $iZettleProduct): int
    {
        return $this->status;
    }

    public function setUpdateStatus(int $status): void
    {
        $this->status = $status;
    }
}
