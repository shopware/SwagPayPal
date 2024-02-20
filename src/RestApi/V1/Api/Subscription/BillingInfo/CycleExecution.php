<?php declare(strict_types=1);
/*
 * (c) shopware AG <info@shopware.com>
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Swag\PayPal\RestApi\V1\Api\Subscription\BillingInfo;

use OpenApi\Attributes as OA;
use Shopware\Core\Framework\Log\Package;
use Swag\PayPal\RestApi\PayPalApiStruct;

/**
 * @codeCoverageIgnore
 *
 * @experimental
 *
 * This class is experimental and not officially supported.
 * It is currently not used within the plugin itself. Use with caution.
 */
#[Package('checkout'), OA\Schema(schema: 'swag_paypal_v1_subscription_billing_info_cycle_execution')]
class CycleExecution extends PayPalApiStruct
{
    #[OA\Property(type: 'string')]
    protected string $tenureType;

    #[OA\Property(type: 'integer')]
    protected int $sequence;

    #[OA\Property(type: 'integer')]
    protected int $cyclesCompleted;

    #[OA\Property(type: 'integer')]
    protected int $cyclesRemaining;

    #[OA\Property(type: 'integer')]
    protected int $totalCycles;

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

    public function getCyclesCompleted(): int
    {
        return $this->cyclesCompleted;
    }

    public function setCyclesCompleted(int $cyclesCompleted): void
    {
        $this->cyclesCompleted = $cyclesCompleted;
    }

    public function getCyclesRemaining(): int
    {
        return $this->cyclesRemaining;
    }

    public function setCyclesRemaining(int $cyclesRemaining): void
    {
        $this->cyclesRemaining = $cyclesRemaining;
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
