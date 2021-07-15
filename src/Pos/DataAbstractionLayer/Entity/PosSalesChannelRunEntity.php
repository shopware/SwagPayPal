<?php declare(strict_types=1);
/*
 * (c) shopware AG <info@shopware.com>
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Swag\PayPal\Pos\DataAbstractionLayer\Entity;

use Shopware\Core\Framework\DataAbstractionLayer\Entity;
use Shopware\Core\Framework\DataAbstractionLayer\EntityIdTrait;

class PosSalesChannelRunEntity extends Entity
{
    use EntityIdTrait;

    /**
     * @var string
     */
    protected $salesChannelId;

    /**
     * @var string
     */
    protected $task;

    /**
     * @var PosSalesChannelRunLogCollection
     */
    protected $logs;

    /**
     * @var \DateTimeInterface|null
     */
    protected $finishedAt;

    protected string $status = PosSalesChannelRunDefinition::STATUS_IN_PROGRESS;

    protected int $messageCount = 0;

    /**
     * @deprecated tag:v4.0.0 - will be removed, use status === PosSalesChannelRunDefinition::STATUS_CANCELLED instead
     *
     * @var bool
     */
    protected $abortedByUser = false;

    public function getSalesChannelId(): string
    {
        return $this->salesChannelId;
    }

    public function setSalesChannelId(string $salesChannelId): void
    {
        $this->salesChannelId = $salesChannelId;
    }

    public function getTask(): string
    {
        return $this->task;
    }

    public function setTask(string $task): void
    {
        $this->task = $task;
    }

    public function getLogs(): PosSalesChannelRunLogCollection
    {
        return $this->logs;
    }

    public function setLogs(PosSalesChannelRunLogCollection $logs): void
    {
        $this->logs = $logs;
    }

    public function getFinishedAt(): ?\DateTimeInterface
    {
        return $this->finishedAt;
    }

    public function setFinishedAt(?\DateTimeInterface $finishedAt): void
    {
        $this->finishedAt = $finishedAt;
    }

    public function getStatus(): string
    {
        return $this->status;
    }

    public function setStatus(string $status): void
    {
        $this->status = $status;
        $this->abortedByUser = $status === PosSalesChannelRunDefinition::STATUS_CANCELLED;
    }

    public function getMessageCount(): int
    {
        return $this->messageCount;
    }

    public function setMessageCount(int $messageCount): void
    {
        $this->messageCount = $messageCount;
    }

    /**
     * @deprecated tag:v4.0.0 - will be removed, use getStatus() === PosSalesChannelRunDefinition::STATUS_CANCELLED instead
     */
    public function getAbortedByUser(): bool
    {
        return $this->status === PosSalesChannelRunDefinition::STATUS_CANCELLED || $this->abortedByUser;
    }

    /**
     * @deprecated tag:v4.0.0 - will be removed, use setStatus(PosSalesChannelRunDefinition::STATUS_CANCELLED) instead
     */
    public function setAbortedByUser(bool $abortedByUser): void
    {
        if ($abortedByUser) {
            $this->status = PosSalesChannelRunDefinition::STATUS_CANCELLED;
        }
        $this->abortedByUser = $abortedByUser;
    }
}
