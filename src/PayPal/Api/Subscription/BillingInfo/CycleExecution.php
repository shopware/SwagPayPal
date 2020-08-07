<?php
declare(strict_types=1);
/*
 * (c) shopware AG <info@shopware.com>
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Swag\PayPal\PayPal\Api\Subscription\BillingInfo;

use Swag\PayPal\PayPal\Api\Common\PayPalStruct;

class CycleExecution extends PayPalStruct
{
    /** @var string */
    protected $tenureType;

    /** @var int */
    protected $sequence;

    /** @var int */
    protected $cyclesCompleted;

    /** @var int */
    protected $cyclesRemaining;

    /** @var int */
    protected $totalCycles;

    public function getTenureType(): string
    {
        return $this->tenureType;
    }

    public function setTenureType(string $tenureType): self
    {
        $this->tenureType = $tenureType;

        return $this;
    }

    public function getSequence(): int
    {
        return $this->sequence;
    }

    public function setSequence(int $sequence): self
    {
        $this->sequence = $sequence;

        return $this;
    }

    public function getCyclesCompleted(): int
    {
        return $this->cyclesCompleted;
    }

    public function setCyclesCompleted(int $cyclesCompleted): self
    {
        $this->cyclesCompleted = $cyclesCompleted;

        return $this;
    }

    public function getCyclesRemaining(): int
    {
        return $this->cyclesRemaining;
    }

    public function setCyclesRemaining(int $cyclesRemaining): self
    {
        $this->cyclesRemaining = $cyclesRemaining;

        return $this;
    }

    public function getTotalCycles(): int
    {
        return $this->totalCycles;
    }

    public function setTotalCycles(int $totalCycles): self
    {
        $this->totalCycles = $totalCycles;

        return $this;
    }
}
