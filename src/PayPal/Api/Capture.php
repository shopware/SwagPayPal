<?php declare(strict_types=1);

namespace Swag\PayPal\PayPal\Api;

use Swag\PayPal\PayPal\Api\Capture\Amount;
use Swag\PayPal\PayPal\Api\Capture\Link;
use Swag\PayPal\PayPal\Api\Capture\TransactionFee;
use Swag\PayPal\PayPal\Api\Common\PayPalStruct;

class Capture extends PayPalStruct
{
    /**
     * @var Amount
     */
    protected $amount;

    /**
     * @var bool
     */
    protected $isFinalCapture;

    /**
     * @var string
     */
    private $id;

    /**
     * @var string
     */
    private $state;

    /**
     * @var string
     */
    private $reasonCode;

    /**
     * @var string
     */
    private $parentPayment;

    /**
     * @var TransactionFee
     */
    private $transactionFee;

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

    public function setAmount(Amount $amount): void
    {
        $this->amount = $amount;
    }

    public function setIsFinalCapture(bool $isFinalCapture): void
    {
        $this->isFinalCapture = $isFinalCapture;
    }

    protected function setId(string $id): void
    {
        $this->id = $id;
    }

    protected function setState(string $state): void
    {
        $this->state = $state;
    }

    protected function setReasonCode(string $reasonCode): void
    {
        $this->reasonCode = $reasonCode;
    }

    protected function setParentPayment(string $parentPayment): void
    {
        $this->parentPayment = $parentPayment;
    }

    protected function setTransactionFee(TransactionFee $transactionFee): void
    {
        $this->transactionFee = $transactionFee;
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
