<?php declare(strict_types=1);
/*
 * (c) shopware AG <info@shopware.com>
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Swag\PayPal\RestApi\V1\Api\Disputes\Common;

use Swag\PayPal\RestApi\PayPalApiStruct;
use Swag\PayPal\RestApi\V1\Api\Common\Money;

abstract class Item extends PayPalApiStruct
{
    /**
     * @var string
     */
    protected $itemId;

    /**
     * @var string
     */
    protected $itemDescription;

    /**
     * @var string
     */
    protected $itemQuantity;

    /**
     * @var string
     */
    protected $partnerTransactionId;

    /**
     * @var string
     */
    protected $reason;

    /**
     * @var Money
     */
    protected $disputeAmount;

    /**
     * @var string
     */
    protected $notes;

    public function getItemId(): string
    {
        return $this->itemId;
    }

    public function setItemId(string $itemId): void
    {
        $this->itemId = $itemId;
    }

    public function getItemDescription(): string
    {
        return $this->itemDescription;
    }

    public function setItemDescription(string $itemDescription): void
    {
        $this->itemDescription = $itemDescription;
    }

    public function getItemQuantity(): string
    {
        return $this->itemQuantity;
    }

    public function setItemQuantity(string $itemQuantity): void
    {
        $this->itemQuantity = $itemQuantity;
    }

    public function getPartnerTransactionId(): string
    {
        return $this->partnerTransactionId;
    }

    public function setPartnerTransactionId(string $partnerTransactionId): void
    {
        $this->partnerTransactionId = $partnerTransactionId;
    }

    public function getReason(): string
    {
        return $this->reason;
    }

    public function setReason(string $reason): void
    {
        $this->reason = $reason;
    }

    public function getDisputeAmount(): Money
    {
        return $this->disputeAmount;
    }

    public function setDisputeAmount(Money $disputeAmount): void
    {
        $this->disputeAmount = $disputeAmount;
    }

    public function getNotes(): string
    {
        return $this->notes;
    }

    public function setNotes(string $notes): void
    {
        $this->notes = $notes;
    }
}
