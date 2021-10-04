<?php declare(strict_types=1);
/*
 * (c) shopware AG <info@shopware.com>
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Swag\PayPal\RestApi\V1\Api;

use OpenApi\Annotations as OA;
use Swag\PayPal\RestApi\PayPalApiStruct;
use Swag\PayPal\RestApi\V1\Api\Refund\Amount;
use Swag\PayPal\RestApi\V1\Api\Refund\Link;
use Swag\PayPal\RestApi\V1\Api\Refund\RefundFromReceivedAmount;
use Swag\PayPal\RestApi\V1\Api\Refund\RefundFromTransactionFee;
use Swag\PayPal\RestApi\V1\Api\Refund\TotalRefundedAmount;

/**
 * @OA\Schema(schema="swag_paypal_v1_refund")
 */
class Refund extends PayPalApiStruct
{
    /**
     * @OA\Property(ref="#/components/schemas/swag_paypal_v1_common_amount")
     */
    protected Amount $amount;

    /**
     * @OA\Property(type="string")
     */
    protected string $invoiceNumber;

    /**
     * @OA\Property(type="string")
     */
    protected string $description;

    /**
     * @OA\Property(type="string")
     */
    protected string $reason;

    /**
     * @OA\Property(type="string")
     */
    protected string $id;

    /**
     * @OA\Property(type="string")
     */
    protected string $createTime;

    /**
     * @OA\Property(type="string")
     */
    protected string $updateTime;

    /**
     * @OA\Property(type="string")
     */
    protected string $state;

    /**
     * @OA\Property(ref="#/components/schemas/swag_paypal_v1_common_value")
     */
    protected RefundFromTransactionFee $refundFromTransactionFee;

    /**
     * @OA\Property(ref="#/components/schemas/swag_paypal_v1_common_value")
     */
    protected TotalRefundedAmount $totalRefundedAmount;

    /**
     * @OA\Property(ref="#/components/schemas/swag_paypal_v1_common_value")
     */
    protected RefundFromReceivedAmount $refundFromReceivedAmount;

    /**
     * @OA\Property(type="string")
     */
    protected string $saleId;

    /**
     * @OA\Property(type="string")
     */
    protected string $captureId;

    /**
     * @OA\Property(type="string")
     */
    protected string $parentPayment;

    /**
     * @var Link[]
     * @OA\Property(type="array", items={"$ref": "#/components/schemas/swag_paypal_v1_common_link"})
     */
    protected array $links;

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
