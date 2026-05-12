<?php declare(strict_types=1);
/*
 * (c) shopware AG <info@shopware.com>
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Swag\PayPal\Pos\MessageQueue\Message;

use Shopware\Core\Framework\Context;
use Shopware\Core\Framework\Log\Package;
use Shopware\Core\Framework\MessageQueue\AsyncMessageInterface;
use Shopware\Core\Framework\Struct\JsonSerializableTrait;
use Shopware\Core\System\SalesChannel\SalesChannelEntity;

#[Package('checkout')]
abstract class AbstractSyncMessage implements AsyncMessageInterface, \JsonSerializable
{
    use JsonSerializableTrait {
        jsonSerialize as traitJsonSerialize;
    }

    protected SalesChannelEntity $salesChannel;

    protected string $salesChannelId;

    protected string $runId;

    protected ?Context $context = null;

    public function getSalesChannel(): SalesChannelEntity
    {
        return $this->salesChannel;
    }

    public function setSalesChannel(SalesChannelEntity $salesChannel): void
    {
        $this->salesChannel = $salesChannel;
        $this->salesChannelId = $salesChannel->getId();
    }

    public function getSalesChannelId(): string
    {
        return $this->salesChannelId;
    }

    public function setSalesChannelId(string $salesChannelId): void
    {
        $this->salesChannelId = $salesChannelId;
    }

    public function getRunId(): string
    {
        return $this->runId;
    }

    public function setRunId(string $runId): void
    {
        $this->runId = $runId;
    }

    public function getContext(): Context
    {
        return $this->context = $this->context ?? Context::createDefaultContext();
    }

    public function jsonSerialize(): array
    {
        $value = $this->traitJsonSerialize();

        unset(
            $value['context'],
            $value['salesChannel'],
            $value['salesChannelContext'],
        );

        return $value;
    }

    public function isHydrated(): bool
    {
        return isset($this->salesChannel);
    }
}
