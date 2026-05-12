<?php declare(strict_types=1);
/*
 * (c) shopware AG <info@shopware.com>
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Swag\PayPal\Pos\MessageQueue\Message;

use Shopware\Core\Framework\Log\Package;
use Swag\PayPal\Pos\MessageQueue\Message\Sync\Traits\OffsetTrait;

#[Package('checkout')]
class CloneVisibilityMessage extends AbstractSyncMessage
{
    use OffsetTrait;

    protected string $fromSalesChannelId;

    protected string $toSalesChannelId;

    public function getFromSalesChannelId(): string
    {
        return $this->fromSalesChannelId;
    }

    public function setFromSalesChannelId(string $fromSalesChannelId): void
    {
        $this->fromSalesChannelId = $fromSalesChannelId;
    }

    public function getToSalesChannelId(): string
    {
        return $this->toSalesChannelId;
    }

    public function setToSalesChannelId(string $toSalesChannelId): void
    {
        $this->toSalesChannelId = $toSalesChannelId;
    }
}
