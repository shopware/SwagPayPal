<?php declare(strict_types=1);
/**
 * (c) shopware AG <info@shopware.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace SwagPayPal\PayPal\Api\Payment\Transaction\RelatedResource;

use SwagPayPal\PayPal\Api\Payment\Transaction\RelatedResource\Sale\Amount;
use SwagPayPal\PayPal\Api\Payment\Transaction\RelatedResource\Sale\Link;
use SwagPayPal\PayPal\Api\Payment\Transaction\RelatedResource\Sale\TransactionFee;
use SwagPayPal\PayPal\Api\PayPalStruct;

class Sale extends PayPalStruct
{
    /**
     * @var string
     */
    private $id;

    /**
     * @var string
     */
    private $state;

    /**
     * @var Amount
     */
    private $amount;

    /**
     * @var string
     */
    private $paymentMode;

    /**
     * @var string
     */
    private $protectionEligibility;

    /**
     * @var string
     */
    private $protectionEligibilityType;

    /**
     * @var TransactionFee
     */
    private $transactionFee;

    /**
     * @var string
     */
    private $parentPayment;

    /**
     * @var string
     */
    private $createTime;

    /**
     * @var string
     */
    private $updateTime;

    /**
     * @var Link[]
     */
    private $links;

    public function getState(): string
    {
        return $this->state;
    }

    protected function setId(string $id): void
    {
        $this->id = $id;
    }

    protected function setState(string $state): void
    {
        $this->state = $state;
    }

    protected function setAmount(Amount $amount): void
    {
        $this->amount = $amount;
    }

    protected function setPaymentMode(string $paymentMode): void
    {
        $this->paymentMode = $paymentMode;
    }

    protected function setProtectionEligibility(string $protectionEligibility): void
    {
        $this->protectionEligibility = $protectionEligibility;
    }

    protected function setProtectionEligibilityType(string $protectionEligibilityType): void
    {
        $this->protectionEligibilityType = $protectionEligibilityType;
    }

    protected function setTransactionFee(TransactionFee $transactionFee): void
    {
        $this->transactionFee = $transactionFee;
    }

    protected function setParentPayment(string $parentPayment): void
    {
        $this->parentPayment = $parentPayment;
    }

    protected function setCreateTime(string $createTime): void
    {
        $this->createTime = $createTime;
    }

    protected function setUpdateTime(string $updateTime): void
    {
        $this->updateTime = $updateTime;
    }

    /**
     * @param Link[] $links
     */
    protected function setLinks(array $links): void
    {
        $this->links = $links;
    }
}
