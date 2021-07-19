<?php declare(strict_types=1);
/*
 * (c) shopware AG <info@shopware.com>
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Swag\PayPal\Pos\MessageQueue\Message;

use Shopware\Core\Framework\Context;
use Swag\PayPal\Pos\MessageQueue\Message\Sync\Traits\OffsetTrait;

class CloneVisibilityMessage extends AbstractSyncMessage
{
    use OffsetTrait;

    private string $fromSalesChannelId;

    private string $toSalesChannelId;

    private Context $context;

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

    public function setContext(Context $context): void
    {
        $this->context = $context;
    }

    public function getContext(): Context
    {
        return $this->context;
    }
}
