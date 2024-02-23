<?php declare(strict_types=1);
/*
 * (c) shopware AG <info@shopware.com>
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Swag\PayPal\RestApi\V1\Api;

use OpenApi\Attributes as OA;
use Shopware\Core\Framework\Log\Package;
use Swag\PayPal\RestApi\PayPalApiStruct;
use Swag\PayPal\RestApi\V1\Api\Common\Amount;
use Swag\PayPal\RestApi\V1\Api\Common\Link;
use Swag\PayPal\RestApi\V1\Api\Common\LinkCollection;
use Swag\PayPal\RestApi\V1\Api\Common\Value;

#[OA\Schema(schema: 'swag_paypal_v1_refund')]
#[Package('checkout')]
class Refund extends PayPalApiStruct
{
    #[OA\Property(ref: Amount::class)]
    protected Amount $amount;

    #[OA\Property(type: 'string')]
    protected string $invoiceNumber;

    #[OA\Property(type: 'string')]
    protected string $description;

    #[OA\Property(type: 'string')]
    protected string $reason;

    #[OA\Property(type: 'string')]
    protected string $id;

    #[OA\Property(type: 'string')]
    protected string $createTime;

    #[OA\Property(type: 'string')]
    protected string $updateTime;

    #[OA\Property(type: 'string')]
    protected string $state;

    #[OA\Property(ref: Value::class)]
    protected Value $refundFromTransactionFee;

    #[OA\Property(ref: Value::class)]
    protected Value $totalRefundedAmount;

    #[OA\Property(ref: Value::class)]
    protected Value $refundFromReceivedAmount;

    #[OA\Property(type: 'string')]
    protected string $saleId;

    #[OA\Property(type: 'string')]
    protected string $captureId;

    #[OA\Property(type: 'string')]
    protected string $parentPayment;

    #[OA\Property(type: 'array', items: new OA\Items(ref: Link::class))]
    protected LinkCollection $links;

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

    public function getRefundFromTransactionFee(): Value
    {
        return $this->refundFromTransactionFee;
    }

    public function setRefundFromTransactionFee(Value $refundFromTransactionFee): void
    {
        $this->refundFromTransactionFee = $refundFromTransactionFee;
    }

    public function getTotalRefundedAmount(): Value
    {
        return $this->totalRefundedAmount;
    }

    public function setTotalRefundedAmount(Value $totalRefundedAmount): void
    {
        $this->totalRefundedAmount = $totalRefundedAmount;
    }

    public function getRefundFromReceivedAmount(): Value
    {
        return $this->refundFromReceivedAmount;
    }

    public function setRefundFromReceivedAmount(Value $refundFromReceivedAmount): void
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

    public function getLinks(): LinkCollection
    {
        return $this->links;
    }

    public function setLinks(LinkCollection $links): void
    {
        $this->links = $links;
    }
}
