<?php declare(strict_types=1);
/*
 * (c) shopware AG <info@shopware.com>
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Swag\PayPal\IZettle\DataAbstractionLayer\Entity;

use Shopware\Core\Framework\DataAbstractionLayer\Entity;
use Shopware\Core\Framework\DataAbstractionLayer\EntityIdTrait;

class IZettleSalesChannelRunEntity extends Entity
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
     * @var IZettleSalesChannelRunLogCollection
     */
    protected $logs;

    /**
     * @var \DateTimeInterface|null
     */
    protected $finishedAt;

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

    public function getLogs(): IZettleSalesChannelRunLogCollection
    {
        return $this->logs;
    }

    public function setLogs(IZettleSalesChannelRunLogCollection $logs): void
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
}
