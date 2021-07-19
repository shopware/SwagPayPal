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
     * @deprecated tag:v4.0.0 - will be strongly typed
     *
     * @var string
     */
    protected $runId;

    /**
     * @deprecated tag:v4.0.0, since 3.0.1 use $posSalesChannelRun instead
     *
     * @var PosSalesChannelRunEntity|null
     */
    protected $run;

    /**
     * @deprecated tag:v4.0.0 - will be strongly typed
     *
     * @var PosSalesChannelRunEntity|null
     */
    protected $posSalesChannelRun;

    /**
     * @deprecated tag:v4.0.0 - will be strongly typed
     *
     * @var int
     */
    protected $level;

    /**
     * @deprecated tag:v4.0.0 - will be strongly typed
     *
     * @var string
     */
    protected $message;

    /**
     * @deprecated tag:v4.0.0 - will be strongly typed
     *
     * @var string|null
     */
    protected $productId;

    /**
     * @deprecated tag:v4.0.0 - will be strongly typed
     *
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

    /**
     * @deprecated tag:v4.0.0, since 3.0.1 use getPosSalesChannelRun() instead
     */
    public function getRun(): ?PosSalesChannelRunEntity
    {
        return $this->getPosSalesChannelRun();
    }

    /**
     * @deprecated tag:v4.0.0, since 3.0.1 use setPosSalesChannelRun() instead
     */
    public function setRun(?PosSalesChannelRunEntity $run): void
    {
        $this->setPosSalesChannelRun($run);
    }

    public function getPosSalesChannelRun(): ?PosSalesChannelRunEntity
    {
        return $this->posSalesChannelRun;
    }

    public function setPosSalesChannelRun(?PosSalesChannelRunEntity $posSalesChannelRun): void
    {
        $this->posSalesChannelRun = $posSalesChannelRun;
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
