<?php declare(strict_types=1);
/**
 * (c) shopware AG <info@shopware.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Swag\PayPal\PayPal\Api\Webhook;

use Swag\PayPal\PayPal\Api\Common\PayPalStruct;
use Swag\PayPal\PayPal\Api\Webhook\Resource\Amount;
use Swag\PayPal\PayPal\Api\Webhook\Resource\Link;
use Swag\PayPal\PayPal\Api\Webhook\Resource\TransactionFee;

class Resource extends PayPalStruct
{
    /**
     * @var string
     */
    protected $parentPayment;

    /**
     * @var string
     */
    private $updateTime;

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
    private $createTime;

    /**
     * @var string
     */
    private $clearingTime;

    /**
     * @var string
     */
    private $protectionEligibilityType;

    /**
     * @var string
     */
    private $protectionEligibility;

    /**
     * @var TransactionFee
     */
    private $transactionFee;

    /**
     * @var string
     */
    private $invoiceNumber;

    /**
     * @var Link[]
     */
    private $links;

    /**
     * @var string
     */
    private $id;

    /**
     * @var string
     */
    private $state;

    public function getParentPayment(): string
    {
        return $this->parentPayment;
    }

    public function setInvoiceNumber(string $invoiceNumber): void
    {
        $this->invoiceNumber = $invoiceNumber;
    }

    protected function setParentPayment(string $parentPayment): void
    {
        $this->parentPayment = $parentPayment;
    }

    protected function setUpdateTime(string $updateTime): void
    {
        $this->updateTime = $updateTime;
    }

    protected function setAmount(Amount $amount): void
    {
        $this->amount = $amount;
    }

    protected function setPaymentMode(string $paymentMode): void
    {
        $this->paymentMode = $paymentMode;
    }

    protected function setCreateTime(string $createTime): void
    {
        $this->createTime = $createTime;
    }

    protected function setClearingTime(string $clearingTime): void
    {
        $this->clearingTime = $clearingTime;
    }

    protected function setProtectionEligibilityType(string $protectionEligibilityType): void
    {
        $this->protectionEligibilityType = $protectionEligibilityType;
    }

    protected function setProtectionEligibility(string $protectionEligibility): void
    {
        $this->protectionEligibility = $protectionEligibility;
    }

    protected function setTransactionFee(TransactionFee $transactionFee): void
    {
        $this->transactionFee = $transactionFee;
    }

    /**
     * @param Link[] $links
     */
    protected function setLinks(array $links): void
    {
        $this->links = $links;
    }

    protected function setId(string $id): void
    {
        $this->id = $id;
    }

    protected function setState(string $state): void
    {
        $this->state = $state;
    }
}
