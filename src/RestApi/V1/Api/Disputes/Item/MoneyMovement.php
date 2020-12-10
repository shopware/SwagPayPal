<?php declare(strict_types=1);
/*
 * (c) shopware AG <info@shopware.com>
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Swag\PayPal\RestApi\V1\Api\Disputes\Item;

use Swag\PayPal\RestApi\PayPalApiStruct;
use Swag\PayPal\RestApi\V1\Api\Disputes\Item\MoneyMovement\Amount;

class MoneyMovement extends PayPalApiStruct
{
    /**
     * @var string
     */
    protected $affectedParty;

    /**
     * @var Amount
     */
    protected $amount;

    /**
     * @var string
     */
    protected $initiatedTime;

    /**
     * @var string
     */
    protected $type;

    /**
     * @var string
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
