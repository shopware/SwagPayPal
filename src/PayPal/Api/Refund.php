<?php declare(strict_types=1);
/*
 * (c) shopware AG <info@shopware.com>
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Swag\PayPal\PayPal\Api;

use Swag\PayPal\PayPal\Api\Common\PayPalStruct;
use Swag\PayPal\PayPal\Api\Refund\Amount;
use Swag\PayPal\PayPal\Api\Refund\Link;
use Swag\PayPal\PayPal\Api\Refund\RefundFromReceivedAmount;
use Swag\PayPal\PayPal\Api\Refund\RefundFromTransactionFee;
use Swag\PayPal\PayPal\Api\Refund\TotalRefundedAmount;

class Refund extends PayPalStruct
{
    /**
     * @var Amount
     */
    protected $amount;

    /**
     * @var string
     */
    protected $invoiceNumber;

    /**
     * @var string
     */
    protected $description;

    /**
     * @var string
     */
    protected $reason;

    /**
     * @var string
     */
    protected $id;

    /**
     * @var string
     */
    protected $createTime;

    /**
     * @var string
     */
    protected $updateTime;

    /**
     * @var string
     */
    protected $state;

    /**
     * @var RefundFromTransactionFee
     */
    protected $refundFromTransactionFee;

    /**
     * @var TotalRefundedAmount
     */
    protected $totalRefundedAmount;

    /**
     * @var RefundFromReceivedAmount
     */
    protected $refundFromReceivedAmount;

    /**
     * @var string
     */
    protected $saleId;

    /**
     * @var string
     */
    protected $captureId;

    /**
     * @var string
     */
    protected $parentPayment;

    /**
     * @var Link[]
     */
    protected $links;

    public function getAmount(): Amount
    {
        return $this->amount;
    }

    public function setAmount(Amount $amount): void
    {
        $this->amount = $amount;
    }

    public function getInvoiceNumber(): string
    {
        return $this->invoiceNumber;
    }

    public function setInvoiceNumber(string $invoiceNumber): void
    {
        $this->invoiceNumber = $invoiceNumber;
    }

    public function getDescription(): string
    {
        return $this->description;
    }

    public function setDescription(string $description): void
    {
        $this->description = $description;
    }

    public function getReason(): string
    {
        return $this->reason;
    }

    public function setReason(string $reason): void
    {
        $this->reason = $reason;
    }

    public function getId(): string
    {
        return $this->id;
    }

    public function setId(string $id): void
    {
        $this->id = $id;
    }

    public function getCreateTime(): string
    {
        return $this->createTime;
    }

    public function setCreateTime(string $createTime): void
    {
        $this->createTime = $createTime;
    }

    public function getUpdateTime(): string
    {
        return $this->updateTime;
    }

    public function setUpdateTime(string $updateTime): void
    {
        $this->updateTime = $updateTime;
    }

    public function getState(): string
    {
        return $this->state;
    }

    public function setState(string $state): void
    {
        $this->state = $state;
    }

    public function getRefundFromTransactionFee(): RefundFromTransactionFee
    {
        return $this->refundFromTransactionFee;
    }

    public function setRefundFromTransactionFee(RefundFromTransactionFee $refundFromTransactionFee): void
    {
        $this->refundFromTransactionFee = $refundFromTransactionFee;
    }

    public function getTotalRefundedAmount(): TotalRefundedAmount
    {
        return $this->totalRefundedAmount;
    }

    public function setTotalRefundedAmount(TotalRefundedAmount $totalRefundedAmount): void
    {
        $this->totalRefundedAmount = $totalRefundedAmount;
    }

    public function getRefundFromReceivedAmount(): RefundFromReceivedAmount
    {
        return $this->refundFromReceivedAmount;
    }

    public function setRefundFromReceivedAmount(RefundFromReceivedAmount $refundFromReceivedAmount): void
    {
        $this->refundFromReceivedAmount = $refundFromReceivedAmount;
    }

    public function getSaleId(): string
    {
        return $this->saleId;
    }

    public function setSaleId(string $saleId): void
    {
        $this->saleId = $saleId;
    }

    public function getCaptureId(): string
    {
        return $this->captureId;
    }

    public function setCaptureId(string $captureId): void
    {
        $this->captureId = $captureId;
    }

    public function getParentPayment(): string
    {
        return $this->parentPayment;
    }

    public function setParentPayment(string $parentPayment): void
    {
        $this->parentPayment = $parentPayment;
    }

    /**
     * @return Link[]
     */
    public function getLinks(): array
    {
        return $this->links;
    }

    /**
     * @param Link[] $links
     */
    public function setLinks(array $links): void
    {
        $this->links = $links;
    }
}
