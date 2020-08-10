<?php declare(strict_types=1);
/*
 * (c) shopware AG <info@shopware.com>
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Swag\PayPal\PayPal\Api\Plan;

use Swag\PayPal\PayPal\Api\Common\PayPalStruct;
use Swag\PayPal\PayPal\Api\Plan\BillingCycle\Frequency;
use Swag\PayPal\PayPal\Api\Plan\BillingCycle\PricingScheme;

/**
 * @codeCoverageIgnore
 * @experimental
 *
 * This class is experimental and not officially supported.
 * It is currently not used within the plugin itself. Use with caution.
 */
class BillingCycle extends PayPalStruct
{
    /**
     * @var Frequency
     */
    protected $frequency;

    /**
     * @var string
     */
    protected $tenureType;

    /**
     * @var int
     */
    protected $sequence;

    /**
     * @var PricingScheme
     */
    protected $pricingScheme;

    /**
     * @var int
     */
    protected $totalCycles;

    public function getFrequency(): Frequency
    {
        return $this->frequency;
    }

    public function setFrequency(Frequency $frequency): void
    {
        $this->frequency = $frequency;
    }

    public function getTenureType(): string
    {
        return $this->tenureType;
    }

    public function setTenureType(string $tenureType): void
    {
        $this->tenureType = $tenureType;
    }

    public function getSequence(): int
    {
        return $this->sequence;
    }

    public function setSequence(int $sequence): void
    {
        $this->sequence = $sequence;
    }

    public function getPricingScheme(): PricingScheme
    {
        return $this->pricingScheme;
    }

    public function setPricingScheme(PricingScheme $pricingScheme): void
    {
        $this->pricingScheme = $pricingScheme;
    }

    public function getTotalCycles(): int
    {
        return $this->totalCycles;
    }

    public function setTotalCycles(int $totalCycles): void
    {
        $this->totalCycles = $totalCycles;
    }
}
