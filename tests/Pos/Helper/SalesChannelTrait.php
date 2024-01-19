<?php

declare(strict_types=1);
/*
 * (c) shopware AG <info@shopware.com>
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Swag\PayPal\Test\Pos\Helper;

use Shopware\Core\Framework\Context;
use Shopware\Core\Framework\DataAbstractionLayer\EntityRepository;
use Shopware\Core\Framework\DataAbstractionLayer\Search\Criteria;
use Shopware\Core\Framework\Log\Package;
use Shopware\Core\Framework\Uuid\Uuid;
use Shopware\Core\System\SalesChannel\SalesChannelEntity;
use Shopware\Core\Test\TestDefaults;
use Swag\PayPal\Pos\DataAbstractionLayer\Entity\PosSalesChannelEntity;
use Swag\PayPal\SwagPayPal;
use Swag\PayPal\Test\Pos\ConstantsForTesting;

/**
 * @internal
 */
#[Package('checkout')]
trait SalesChannelTrait
{
    protected function getSalesChannel(Context $context): SalesChannelEntity
    {
        /** @var EntityRepository $salesChannelRepository */
        $salesChannelRepository = $this->getContainer()->get('sales_channel.repository');
        $salesChannelCriteria = new Criteria([TestDefaults::SALES_CHANNEL]);
        $salesChannelCriteria->addAssociation('currency');

        /** @var SalesChannelEntity $salesChannel */
        $salesChannel = $salesChannelRepository->search($salesChannelCriteria, $context)->first();
        $posSalesChannel = new PosSalesChannelEntity();
        $posSalesChannel->setId(Uuid::randomHex());
        $posSalesChannel->setSalesChannelId($salesChannel->getId());
        $posSalesChannel->setMediaDomain(ConstantsForTesting::DOMAIN);
        $posSalesChannel->setApiKey(ConstantsForTesting::VALID_API_KEY);
        $posSalesChannel->setReplace(PosSalesChannelEntity::REPLACE_PERMANENTLY);
        $posSalesChannel->setSyncPrices(true);
        $posSalesChannel->setProductStreamId(null);
        $posSalesChannel->setWebhookSigningKey(ConstantsForTesting::WEBHOOK_SIGNING_KEY);
        $salesChannel->setTypeId(SwagPayPal::SALES_CHANNEL_TYPE_POS);
        $salesChannel->addExtension(SwagPayPal::SALES_CHANNEL_POS_EXTENSION, $posSalesChannel);

        return $salesChannel;
    }
}
