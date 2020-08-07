<?php
declare(strict_types=1);
/*
 * (c) shopware AG <info@shopware.com>
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Swag\PayPal\PayPal\Api\Subscription;

use Swag\PayPal\PayPal\Api\Common\PayPalStruct;
use Swag\PayPal\PayPal\Api\Subscription\BillingInfo\CycleExecution;
use Swag\PayPal\PayPal\Api\Subscription\BillingInfo\LastPayment;
use Swag\PayPal\PayPal\Api\Subscription\BillingInfo\OutstandingBalance;

class BillingInfo extends PayPalStruct
{
    /** @var OutstandingBalance */
    protected $outstandingBalance;

    /** @var CycleExecution[] */
    protected $cycleExecutions = [];

    /** @var LastPayment */
    protected $lastPayment;

    /** @var string */
    protected $nextBillingTime;

    /** @var int */
    protected $failedPaymentsCount;

    public function getOutstandingBalance(): OutstandingBalance
    {
        return $this->outstandingBalance;
    }

    public function setOutstandingBalance(OutstandingBalance $outstandingBalance): self
    {
        $this->outstandingBalance = $outstandingBalance;

        return $this;
    }

    /**
     * @return CycleExecution[]
     */
    public function getCycleExecutions(): array
    {
        return $this->cycleExecutions;
    }

    /**
     * @param CycleExecution[] $cycleExecutions
     */
    public function setCycleExecutions(array $cycleExecutions): self
    {
        $this->cycleExecutions = $cycleExecutions;

        return $this;
    }

    public function getLastPayment(): LastPayment
    {
        return $this->lastPayment;
    }

    public function setLastPayment(LastPayment $lastPayment): self
    {
        $this->lastPayment = $lastPayment;

        return $this;
    }

    public function getNextBillingTime(): string
    {
        return $this->nextBillingTime;
    }

    public function setNextBillingTime(string $nextBillingTime): self
    {
        $this->nextBillingTime = $nextBillingTime;

        return $this;
    }

    public function getFailedPaymentsCount(): int
    {
        return $this->failedPaymentsCount;
    }

    public function setFailedPaymentsCount(int $failedPaymentsCount): self
    {
        $this->failedPaymentsCount = $failedPaymentsCount;

        return $this;
    }
}
