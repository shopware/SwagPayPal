<?php declare(strict_types=1);
/*
 * (c) shopware AG <info@shopware.com>
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Swag\PayPal\Pos\DataAbstractionLayer\Entity;

use Shopware\Core\Framework\DataAbstractionLayer\Entity;
use Shopware\Core\Framework\DataAbstractionLayer\EntityIdTrait;
use Shopware\Core\Framework\Log\Package;

#[Package('checkout')]
class PosSalesChannelRunEntity extends Entity
{
    use EntityIdTrait;

    protected string $salesChannelId;

    protected string $task;

    protected ?PosSalesChannelRunLogCollection $logs = null;

    protected ?\DateTimeInterface $finishedAt = null;

    protected string $status = PosSalesChannelRunDefinition::STATUS_IN_PROGRESS;

    protected int $stepIndex = 0;

    /**
     * @var string[]
     */
    protected array $steps;

    protected int $messageCount = 0;

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

    public function getLogs(): ?PosSalesChannelRunLogCollection
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
    }

    public function getMessageCount(): int
    {
        return $this->messageCount;
    }

    public function setMessageCount(int $messageCount): void
    {
        $this->messageCount = $messageCount;
    }

    public function getStepIndex(): int
    {
        return $this->stepIndex;
    }

    public function setStepIndex(int $stepIndex): void
    {
        $this->stepIndex = $stepIndex;
    }

    /**
     * @return string[]
     */
    public function getSteps(): array
    {
        return $this->steps;
    }

    /**
     * @param string[] $steps
     */
    public function setSteps(array $steps): void
    {
        $this->steps = $steps;
    }
}
