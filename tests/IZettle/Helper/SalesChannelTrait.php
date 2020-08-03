<?php
declare(strict_types=1);
/*
 * (c) shopware AG <info@shopware.com>
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Swag\PayPal\Test\IZettle\Helper;

use Shopware\Core\Defaults;
use Shopware\Core\Framework\Context;
use Shopware\Core\Framework\DataAbstractionLayer\EntityRepositoryInterface;
use Shopware\Core\Framework\DataAbstractionLayer\Search\Criteria;
use Shopware\Core\Framework\Uuid\Uuid;
use Shopware\Core\System\SalesChannel\SalesChannelEntity;
use Swag\PayPal\IZettle\DataAbstractionLayer\Entity\IZettleSalesChannelEntity;
use Swag\PayPal\SwagPayPal;
use Swag\PayPal\Test\IZettle\ConstantsForTesting;

trait SalesChannelTrait
{
    protected function getSalesChannel(Context $context): SalesChannelEntity
    {
        /** @var EntityRepositoryInterface $salesChannelRepository */
        $salesChannelRepository = $this->getContainer()->get('sales_channel.repository');
        $salesChannelCriteria = new Criteria([Defaults::SALES_CHANNEL]);
        $salesChannelCriteria->addAssociation('currency');

        /** @var SalesChannelEntity $salesChannel */
        $salesChannel = $salesChannelRepository->search($salesChannelCriteria, $context)->first();
        $iZettleSalesChannel = new IZettleSalesChannelEntity();
        $iZettleSalesChannel->setId(Uuid::randomHex());
        $iZettleSalesChannel->setSalesChannelId($salesChannel->getId());
        $iZettleSalesChannel->setMediaDomain(ConstantsForTesting::DOMAIN);
        $iZettleSalesChannel->setApiKey(ConstantsForTesting::VALID_API_KEY);
        $iZettleSalesChannel->setReplace(true);
        $iZettleSalesChannel->setSyncPrices(true);
        $iZettleSalesChannel->setProductStreamId(null);
        $salesChannel->setTypeId(SwagPayPal::SALES_CHANNEL_TYPE_IZETTLE);
        $salesChannel->addExtension(SwagPayPal::SALES_CHANNEL_IZETTLE_EXTENSION, $iZettleSalesChannel);

        return $salesChannel;
    }
}
