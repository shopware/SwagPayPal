<?php declare(strict_types=1);
/*
 * (c) shopware AG <info@shopware.com>
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Swag\PayPal\Pos\DataAbstractionLayer\Entity;

use Shopware\Core\Framework\DataAbstractionLayer\Entity;
use Shopware\Core\Framework\DataAbstractionLayer\EntityIdTrait;

class PosSalesChannelRunLogEntity extends Entity
{
    use EntityIdTrait;

    /**
     * @var string
     */
    protected $runId;

    /**
     * @var PosSalesChannelRunEntity
     */
    protected $run;

    /**
     * @var int
     */
    protected $level;

    /**
     * @var string
     */
    protected $message;

    /**
     * @var string|null
     */
    protected $productId;

    /**
     * @var string|null
     */
    protected $productVersionId;

    public function getRunId(): string
    {
        return $this->runId;
    }

    public function setRunId(string $runId): void
    {
        $this->runId = $runId;
    }

    public function getRun(): PosSalesChannelRunEntity
    {
        return $this->run;
    }

    public function setRun(PosSalesChannelRunEntity $run): void
    {
        $this->run = $run;
    }

    public function getLevel(): int
    {
        return $this->level;
    }

    public function setLevel(int $level): void
    {
        $this->level = $level;
    }

    public function getMessage(): string
    {
        return $this->message;
    }

    public function setMessage(string $message): void
    {
        $this->message = $message;
    }

    public function getProductId(): ?string
    {
        return $this->productId;
    }

    public function setProductId(?string $productId): void
    {
        $this->productId = $productId;
    }

    public function getProductVersionId(): ?string
    {
        return $this->productVersionId;
    }

    public function setProductVersionId(?string $productVersionId): void
    {
        $this->productVersionId = $productVersionId;
    }
}
