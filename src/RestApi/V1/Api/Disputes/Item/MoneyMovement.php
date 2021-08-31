<?php declare(strict_types=1);
/*
 * (c) shopware AG <info@shopware.com>
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Swag\PayPal\RestApi\V1\Api\Disputes\Item;

use OpenApi\Annotations as OA;
use Swag\PayPal\RestApi\PayPalApiStruct;
use Swag\PayPal\RestApi\V1\Api\Disputes\Item\MoneyMovement\Amount;

/**
 * @OA\Schema(schema="swag_paypal_v1_disputes_money_movement")
 */
class MoneyMovement extends PayPalApiStruct
{
    /**
     * @deprecated tag:v4.0.0 - will be strongly typed
     *
     * @var string
     * @OA\Property(type="string")
     */
    protected $affectedParty;

    /**
     * @deprecated tag:v4.0.0 - will be strongly typed
     *
     * @var Amount
     * @OA\Property(ref="#/components/schemas/swag_paypal_v1_common_money")
     */
    protected $amount;

    /**
     * @deprecated tag:v4.0.0 - will be strongly typed
     *
     * @var string
     * @OA\Property(type="string")
     */
    protected $initiatedTime;

    /**
     * @deprecated tag:v4.0.0 - will be strongly typed
     *
     * @var string
     * @OA\Property(type="string")
     */
    protected $type;

    /**
     * @deprecated tag:v4.0.0 - will be strongly typed
     *
     * @var string
     * @OA\Property(type="string")
     */
    protected $reason;

    public function getAffectedParty(): string
    {
        return $this->affectedParty;
    }

    public function setAffectedParty(string $affectedParty): void
    {
        $this->affectedParty = $affectedParty;
    }

    public function getAmount(): Amount
    {
        return $this->amount;
    }

    public function setAmount(Amount $amount): void
    {
        $this->amount = $amount;
    }

    public function getInitiatedTime(): string
    {
        return $this->initiatedTime;
    }

    public function setInitiatedTime(string $initiatedTime): void
    {
        $this->initiatedTime = $initiatedTime;
    }

    public function getType(): string
    {
        return $this->type;
    }

    public function setType(string $type): void
    {
        $this->type = $type;
    }

    public function getReason(): string
    {
        return $this->reason;
    }

    public function setReason(string $reason): void
    {
        $this->reason = $reason;
    }
}
