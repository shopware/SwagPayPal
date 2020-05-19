<?php declare(strict_types=1);
/*
 * (c) shopware AG <info@shopware.com>
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Swag\PayPal\Test\IZettle\Sync\Product;

use Shopware\Core\Defaults;
use Shopware\Core\Framework\Context;
use Shopware\Core\Framework\DataAbstractionLayer\EntityRepositoryInterface;
use Shopware\Core\Framework\DataAbstractionLayer\Search\Criteria;
use Shopware\Core\Framework\Test\TestCaseBase\KernelTestBehaviour;
use Shopware\Core\System\SalesChannel\SalesChannelEntity;
use Swag\PayPal\IZettle\DataAbstractionLayer\Entity\IZettleSalesChannelEntity;

trait ProductTrait
{
    use KernelTestBehaviour;

    private function createSalesChannel(Context $context): SalesChannelEntity
    {
        $criteria = new Criteria();
        $criteria->setIds([Defaults::SALES_CHANNEL]);
        $criteria->addAssociation('currency');

        /** @var EntityRepositoryInterface $salesChannelRepository */
        $salesChannelRepository = $this->getContainer()->get('sales_channel.repository');

        /** @var SalesChannelEntity $salesChannel */
        $salesChannel = $salesChannelRepository->search($criteria, $context)->first();

        $iZettleSalesChannel = new IZettleSalesChannelEntity();
        if (\random_int(0, 1)) {
            $iZettleSalesChannel->setProductStreamId('someProductStreamId');
        }
        $iZettleSalesChannel->setSyncPrices(true);
        $iZettleSalesChannel->setSalesChannelDomainId('someSalesChannelDomainId');

        $salesChannel->addExtension('paypalIZettleSalesChannel', $iZettleSalesChannel);

        return $salesChannel;
    }
}
