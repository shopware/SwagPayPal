<?php declare(strict_types=1);
/*
 * (c) shopware AG <info@shopware.com>
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Swag\PayPal\Test\IZettle\Sync\Inventory;

use Shopware\Core\Content\Product\ProductEntity;
use Shopware\Core\Defaults;
use Shopware\Core\Framework\Uuid\Uuid;
use Swag\PayPal\IZettle\DataAbstractionLayer\Entity\IZettleSalesChannelEntity;

trait InventoryTrait
{
    /**
     * @var string[]
     */
    protected $locations = [
        'STORE' => 'storeUuid',
        'BIN' => 'binUuid',
        'SUPPLIER' => 'supplierUuid',
        'SOLD' => 'soldUuid',
    ];

    protected function getVariantProduct(): ProductEntity
    {
        $product = new ProductEntity();
        $product->setId('4191c1b4c6af4f5782a7604aa9ae3222');
        $product->setVersionId('7c1da595-2b4c-4c25-afa7-8dcf5d3adca0');
        $product->setParentId('3f5fa7e700714b2082e6c63ab14206da');
        $product->setStock(1);
        $product->setAvailableStock(1);

        return $product;
    }

    protected function getSingleProduct(): ProductEntity
    {
        $product = new ProductEntity();
        $product->setId('1846c887e4174fde9009d9d7d6eae238');
        $product->setVersionId('7c1da595-2b4c-4c25-afa7-8dcf5d3adca0');
        $product->setStock(3);
        $product->setAvailableStock(2);

        return $product;
    }

    protected function getIZettleSalesChannel(): IZettleSalesChannelEntity
    {
        $iZettleSalesChannel = new IZettleSalesChannelEntity();
        $iZettleSalesChannel->setSyncPrices(true);
        $iZettleSalesChannel->setSalesChannelDomainId('someSalesChannelDomainId');
        $iZettleSalesChannel->setUniqueIdentifier(Uuid::randomHex());
        $iZettleSalesChannel->setId(Uuid::randomHex());
        $iZettleSalesChannel->setSalesChannelId(Defaults::SALES_CHANNEL);

        return $iZettleSalesChannel;
    }
}
