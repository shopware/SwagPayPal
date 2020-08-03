<?php declare(strict_types=1);
/*
 * (c) shopware AG <info@shopware.com>
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Swag\PayPal\IZettle\Sync;

use Shopware\Core\System\SalesChannel\SalesChannelEntity;
use Swag\PayPal\IZettle\DataAbstractionLayer\Entity\IZettleSalesChannelEntity;
use Swag\PayPal\IZettle\Exception\UnexpectedSalesChannelTypeException;
use Swag\PayPal\SwagPayPal;

abstract class AbstractSyncer
{
    protected function getIZettleSalesChannel(SalesChannelEntity $salesChannel): IZettleSalesChannelEntity
    {
        /** @var IZettleSalesChannelEntity|null $iZettleSalesChannel */
        $iZettleSalesChannel = $salesChannel->getExtension(SwagPayPal::SALES_CHANNEL_IZETTLE_EXTENSION);

        if ($iZettleSalesChannel === null) {
            throw new UnexpectedSalesChannelTypeException($salesChannel->getTypeId());
        }

        return $iZettleSalesChannel;
    }
}
