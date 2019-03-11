<?php declare(strict_types=1);
/**
 * (c) shopware AG <info@shopware.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace SwagPayPal\PayPal\Api;

use SwagPayPal\PayPal\Api\Common\PayPalStruct;
use SwagPayPal\PayPal\Api\Refund\Amount;
use SwagPayPal\PayPal\Api\Refund\Link;
use SwagPayPal\PayPal\Api\Refund\RefundFromReceivedAmount;
use SwagPayPal\PayPal\Api\Refund\RefundFromTransactionFee;
use SwagPayPal\PayPal\Api\Refund\TotalRefundedAmount;

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
    private $id;

    /**
     * @var string
     */
    private $createTime;

    /**
     * @var string
     */
    private $updateTime;

    /**
     * @var string
     */
    private $state;

    /**
     * @var RefundFromTransactionFee
     */
    private $refundFromTransactionFee;

    /**
     * @var TotalRefundedAmount
     */
    private $totalRefundedAmount;

    /**
     * @var RefundFromReceivedAmount
     */
    private $refundFromReceivedAmount;

    /**
     * @var string
     */
    private $saleId;

    /**
     * @var string
     */
    private $parentPayment;

    /**
     * @var Link[]
     */
    private $links;

    public function setAmount(Amount $amount): void
    {
        $this->amount = $amount;
    }

    public function setInvoiceNumber(string $invoiceNumber): void
    {
        $this->invoiceNumber = $invoiceNumber;
    }

    public function getState(): string
    {
        return $this->state;
    }

    protected function setId(string $id): void
    {
        $this->id = $id;
    }

    protected function setCreateTime(string $createTime): void
    {
        $this->createTime = $createTime;
    }

    protected function setUpdateTime(string $updateTime): void
    {
        $this->updateTime = $updateTime;
    }

    protected function setState(string $state): void
    {
        $this->state = $state;
    }

    protected function setRefundFromTransactionFee(RefundFromTransactionFee $refundFromTransactionFee): void
    {
        $this->refundFromTransactionFee = $refundFromTransactionFee;
    }

    protected function setTotalRefundedAmount(TotalRefundedAmount $totalRefundedAmount): void
    {
        $this->totalRefundedAmount = $totalRefundedAmount;
    }

    protected function setRefundFromReceivedAmount(RefundFromReceivedAmount $refundFromReceivedAmount): void
    {
        $this->refundFromReceivedAmount = $refundFromReceivedAmount;
    }

    protected function setSaleId(string $saleId): void
    {
        $this->saleId = $saleId;
    }

    protected function setParentPayment(string $parentPayment): void
    {
        $this->parentPayment = $parentPayment;
    }

    /**
     * @param Link[] $links
     */
    protected function setLinks(array $links): void
    {
        $this->links = $links;
    }
}
