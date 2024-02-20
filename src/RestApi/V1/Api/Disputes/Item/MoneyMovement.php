<?php declare(strict_types=1);
/*
 * (c) shopware AG <info@shopware.com>
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Swag\PayPal\RestApi\V1\Api\Disputes\Item;

use OpenApi\Attributes as OA;
use Shopware\Core\Framework\Log\Package;
use Swag\PayPal\RestApi\PayPalApiStruct;
use Swag\PayPal\RestApi\V1\Api\Common\Amount;

#[OA\Schema(schema: 'swag_paypal_v1_disputes_item_money_movement')]
#[Package('checkout')]
class MoneyMovement extends PayPalApiStruct
{
    #[OA\Property(type: 'string')]
    protected string $affectedParty;

    #[OA\Property(ref: Amount::class)]
    protected Amount $amount;

    #[OA\Property(type: 'string')]
    protected string $initiatedTime;

    #[OA\Property(type: 'string')]
    protected string $type;

    #[OA\Property(type: 'string')]
    protected string $reason;

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
