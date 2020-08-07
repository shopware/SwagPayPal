<?php
declare(strict_types=1);
/*
 * (c) shopware AG <info@shopware.com>
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Swag\PayPal\PayPal\Api\Plan;

use Swag\PayPal\PayPal\Api\Common\PayPalStruct;
use Swag\PayPal\PayPal\Api\Plan\BillingCycle\Frequency;
use Swag\PayPal\PayPal\Api\Plan\BillingCycle\PricingScheme;

class BillingCycle extends PayPalStruct
{
    /** @var Frequency */
    protected $frequency;

    /** @var string */
    protected $tenureType;

    /** @var int */
    protected $sequence;

    /** @var PricingScheme */
    protected $pricingScheme;

    /** @var int */
    protected $totalCycles;

    public function getFrequency(): Frequency
    {
        return $this->frequency;
    }

    public function setFrequency(Frequency $frequency): self
    {
        $this->frequency = $frequency;

        return $this;
    }

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

    public function getPricingScheme(): PricingScheme
    {
        return $this->pricingScheme;
    }

    public function setPricingScheme(PricingScheme $pricingScheme): self
    {
        $this->pricingScheme = $pricingScheme;

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
