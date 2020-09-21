<?php
declare(strict_types=1);
/*
 * (c) shopware AG <info@shopware.com>
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Swag\PayPal\Test\Pos\Helper;

use Shopware\Core\Defaults;
use Shopware\Core\Framework\Context;
use Shopware\Core\Framework\DataAbstractionLayer\EntityRepositoryInterface;
use Shopware\Core\Framework\DataAbstractionLayer\Search\Criteria;
use Shopware\Core\Framework\Uuid\Uuid;
use Shopware\Core\System\SalesChannel\SalesChannelEntity;
use Swag\PayPal\Pos\DataAbstractionLayer\Entity\PosSalesChannelEntity;
use Swag\PayPal\SwagPayPal;
use Swag\PayPal\Test\Pos\ConstantsForTesting;

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
        $posSalesChannel = new PosSalesChannelEntity();
        $posSalesChannel->setId(Uuid::randomHex());
        $posSalesChannel->setSalesChannelId($salesChannel->getId());
        $posSalesChannel->setMediaDomain(ConstantsForTesting::DOMAIN);
        $posSalesChannel->setApiKey(ConstantsForTesting::VALID_API_KEY);
        $posSalesChannel->setReplace(true);
        $posSalesChannel->setSyncPrices(true);
        $posSalesChannel->setProductStreamId(null);
        $posSalesChannel->setWebhookSigningKey(ConstantsForTesting::WEBHOOK_SIGNING_KEY);
        $salesChannel->setTypeId(SwagPayPal::SALES_CHANNEL_TYPE_POS);
        $salesChannel->addExtension(SwagPayPal::SALES_CHANNEL_POS_EXTENSION, $posSalesChannel);

        return $salesChannel;
    }
}
