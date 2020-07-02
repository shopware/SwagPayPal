<?php declare(strict_types=1);
/*
 * (c) shopware AG <info@shopware.com>
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Swag\PayPal\Test\IZettle\Sync\Inventory;

use Shopware\Core\Content\Product\SalesChannel\SalesChannelProductEntity;
use Shopware\Core\Defaults;
use Shopware\Core\Framework\Context;
use Shopware\Core\Framework\DataAbstractionLayer\EntityRepositoryInterface;
use Shopware\Core\Framework\DataAbstractionLayer\Search\Criteria;
use Shopware\Core\Framework\Test\TestCaseBase\KernelTestBehaviour;
use Shopware\Core\Framework\Uuid\Uuid;
use Shopware\Core\System\SalesChannel\Aggregate\SalesChannelDomain\SalesChannelDomainEntity;
use Shopware\Core\System\SalesChannel\SalesChannelEntity;
use Swag\PayPal\IZettle\DataAbstractionLayer\Entity\IZettleSalesChannelEntity;
use Swag\PayPal\SwagPayPal;

trait InventoryTrait
{
    use KernelTestBehaviour;

    /**
     * @var string[]
     */
    protected $locations = [
        'STORE' => 'storeUuid',
        'BIN' => 'binUuid',
        'SUPPLIER' => 'supplierUuid',
        'SOLD' => 'soldUuid',
    ];

    protected function getVariantProduct(): SalesChannelProductEntity
    {
        $product = new SalesChannelProductEntity();
        $product->setId('4191c1b4c6af4f5782a7604aa9ae3222');
        $product->setVersionId('7c1da595-2b4c-4c25-afa7-8dcf5d3adca0');
        $product->setParentId('3f5fa7e700714b2082e6c63ab14206da');
        $product->setStock(1);
        $product->setAvailableStock(1);

        return $product;
    }

    protected function getSingleProduct(): SalesChannelProductEntity
    {
        $product = new SalesChannelProductEntity();
        $product->setId('1846c887e4174fde9009d9d7d6eae238');
        $product->setVersionId('7c1da595-2b4c-4c25-afa7-8dcf5d3adca0');
        $product->setStock(3);
        $product->setAvailableStock(2);

        return $product;
    }

    protected function createSalesChannel(Context $context): SalesChannelEntity
    {
        $criteria = new Criteria();
        $criteria->setIds([Defaults::SALES_CHANNEL]);
        $criteria->addAssociation('currency');

        /** @var EntityRepositoryInterface $salesChannelRepository */
        $salesChannelRepository = $this->getContainer()->get('sales_channel.repository');

        /** @var SalesChannelEntity $salesChannel */
        $salesChannel = $salesChannelRepository->search($criteria, $context)->first();

        $iZettleSalesChannel = new IZettleSalesChannelEntity();
        $iZettleSalesChannel->setSyncPrices(true);
        $iZettleSalesChannel->setUniqueIdentifier(Uuid::randomHex());
        $iZettleSalesChannel->setId(Uuid::randomHex());
        $iZettleSalesChannel->setSalesChannelId(Defaults::SALES_CHANNEL);

        $domain = new SalesChannelDomainEntity();
        $domain->setId(Uuid::randomHex());
        $domain->setSalesChannelId(Defaults::SALES_CHANNEL);
        $domain->setLanguageId(Uuid::randomHex());
        $iZettleSalesChannel->setSalesChannelDomain($domain);
        $iZettleSalesChannel->setSalesChannelDomainId($domain->getId());

        $salesChannel->addExtension(SwagPayPal::SALES_CHANNEL_IZETTLE_EXTENSION, $iZettleSalesChannel);

        return $salesChannel;
    }
}
