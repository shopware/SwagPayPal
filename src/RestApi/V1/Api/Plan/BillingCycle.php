<?php declare(strict_types=1);
/*
 * (c) shopware AG <info@shopware.com>
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Swag\PayPal\RestApi\V1\Api\Plan;

use OpenApi\Attributes as OA;
use Shopware\Core\Framework\Log\Package;
use Swag\PayPal\RestApi\PayPalApiStruct;
use Swag\PayPal\RestApi\V1\Api\Plan\BillingCycle\Frequency;
use Swag\PayPal\RestApi\V1\Api\Plan\BillingCycle\PricingScheme;

/**
 * @codeCoverageIgnore
 *
 * @experimental
 *
 * This class is experimental and not officially supported.
 * It is currently not used within the plugin itself. Use with caution.
 */
#[OA\Schema(schema: 'swag_paypal_v1_plan_billing_cycle')]
#[Package('checkout')]
class BillingCycle extends PayPalApiStruct
{
    #[OA\Property(ref: Frequency::class)]
    protected Frequency $frequency;

    #[OA\Property(type: 'string')]
    protected string $tenureType;

    #[OA\Property(type: 'integer')]
    protected int $sequence;

    #[OA\Property(ref: PricingScheme::class)]
    protected PricingScheme $pricingScheme;

    #[OA\Property(type: 'integer')]
    protected int $totalCycles;

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
