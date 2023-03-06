<?php declare(strict_types=1);
/*
 * (c) shopware AG <info@shopware.com>
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Swag\PayPal\RestApi\V1\Api\Subscription;

use OpenApi\Annotations as OA;
use Swag\PayPal\RestApi\PayPalApiStruct;
use Swag\PayPal\RestApi\V1\Api\Subscription\BillingInfo\CycleExecution;
use Swag\PayPal\RestApi\V1\Api\Subscription\BillingInfo\LastPayment;
use Swag\PayPal\RestApi\V1\Api\Subscription\BillingInfo\OutstandingBalance;

/**
 * @OA\Schema(schema="swag_paypal_v1_subscription_billing_info")
 *
 * @codeCoverageIgnore
 *
 * @experimental
 *
 * This class is experimental and not officially supported.
 * It is currently not used within the plugin itself. Use with caution.
 */
class BillingInfo extends PayPalApiStruct
{
    /**
     * @OA\Property(ref="#/components/schemas/swag_paypal_v1_common_money")
     */
    protected OutstandingBalance $outstandingBalance;

    /**
     * @var CycleExecution[]
     *
     * @OA\Property(type="array", items={"$ref": "#/components/schemas/swag_paypal_v1_subscription_cycle_execution"})
     */
    protected array $cycleExecutions = [];

    /**
     * @OA\Property(ref="#/components/schemas/swag_paypal_v1_subscription_last_payment")
     */
    protected LastPayment $lastPayment;

    /**
     * @OA\Property(type="string", nullable=true)
     */
    protected ?string $nextBillingTime = null;

    /**
     * @OA\Property(type="integer")
     */
    protected int $failedPaymentsCount;

    public function getOutstandingBalance(): OutstandingBalance
    {
        return $this->outstandingBalance;
    }

    public function setOutstandingBalance(OutstandingBalance $outstandingBalance): void
    {
        $this->outstandingBalance = $outstandingBalance;
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
    public function setCycleExecutions(array $cycleExecutions): void
    {
        $this->cycleExecutions = $cycleExecutions;
    }

    public function getLastPayment(): LastPayment
    {
        return $this->lastPayment;
    }

    public function setLastPayment(LastPayment $lastPayment): void
    {
        $this->lastPayment = $lastPayment;
    }

    public function getNextBillingTime(): ?string
    {
        return $this->nextBillingTime;
    }

    public function setNextBillingTime(?string $nextBillingTime): void
    {
        $this->nextBillingTime = $nextBillingTime;
    }

    public function getFailedPaymentsCount(): int
    {
        return $this->failedPaymentsCount;
    }

    public function setFailedPaymentsCount(int $failedPaymentsCount): void
    {
        $this->failedPaymentsCount = $failedPaymentsCount;
    }
}
