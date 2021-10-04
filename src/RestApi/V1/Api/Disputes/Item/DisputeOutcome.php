<?php declare(strict_types=1);
/*
 * (c) shopware AG <info@shopware.com>
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Swag\PayPal\RestApi\V1\Api\Disputes\Item;

use OpenApi\Annotations as OA;
use Swag\PayPal\RestApi\PayPalApiStruct;
use Swag\PayPal\RestApi\V1\Api\Disputes\Item\DisputeOutcome\AmountRefunded;

/**
 * @OA\Schema(schema="swag_paypal_v1_disputes_dispute_outcome")
 */
class DisputeOutcome extends PayPalApiStruct
{
    /**
     * @OA\Property(type="string")
     */
    protected string $outcomeCode;

    /**
     * @OA\Property(ref="#/components/schemas/swag_paypal_v1_common_money")
     */
    protected AmountRefunded $amountRefunded;

    public function getOutcomeCode(): string
    {
        return $this->outcomeCode;
    }

    public function setOutcomeCode(string $outcomeCode): void
    {
        $this->outcomeCode = $outcomeCode;
    }

    public function getAmountRefunded(): AmountRefunded
    {
        return $this->amountRefunded;
    }

    public function setAmountRefunded(AmountRefunded $amountRefunded): void
    {
        $this->amountRefunded = $amountRefunded;
    }
}
