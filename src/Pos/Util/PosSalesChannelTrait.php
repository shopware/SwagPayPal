<?php declare(strict_types=1);
/*
 * (c) shopware AG <info@shopware.com>
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Swag\PayPal\Pos\Util;

use Shopware\Core\Framework\Log\Package;
use Shopware\Core\System\SalesChannel\SalesChannelEntity;
use Swag\PayPal\Pos\DataAbstractionLayer\Entity\PosSalesChannelEntity;
use Swag\PayPal\Pos\Exception\UnexpectedSalesChannelTypeException;
use Swag\PayPal\SwagPayPal;

#[Package('checkout')]
trait PosSalesChannelTrait
{
    protected function getPosSalesChannel(SalesChannelEntity $salesChannel): PosSalesChannelEntity
    {
        /** @var PosSalesChannelEntity|null $posSalesChannel */
        $posSalesChannel = $salesChannel->getExtension(SwagPayPal::SALES_CHANNEL_POS_EXTENSION);

        if ($posSalesChannel === null) {
            throw new UnexpectedSalesChannelTypeException($salesChannel->getTypeId());
        }

        return $posSalesChannel;
    }
}
