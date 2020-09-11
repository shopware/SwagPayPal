<?php declare(strict_types=1);
/*
 * (c) shopware AG <info@shopware.com>
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Swag\PayPal\RestApi\V1\Api\Payment\Transaction\RelatedResource;

use Swag\PayPal\RestApi\PayPalApiStruct;
use Swag\PayPal\RestApi\V1\Api\Common\Amount;
use Swag\PayPal\RestApi\V1\Api\Common\Link;

abstract class RelatedResource extends PayPalApiStruct
{
    /**
     * @var string
     */
    protected $id;

    /**
     * @var string
     */
    protected $state;

    /**
     * @var Amount
     */
    protected $amount;

    /**
     * @var string
     */
    protected $paymentMode;

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
    protected $protectionEligibility;

    /**
     * @var string
     */
    protected $protectionEligibilityType;

    /**
     * @var string
     */
    protected $receiptId;

    /**
     * @var string
     */
    protected $parentPayment;

    /**
     * @var Link[]
     */
    protected $links;

    public function getId(): string
    {
        return $this->id;
    }

    public function setId(string $id): void
    {
        $this->id = $id;
    }

    public function getState(): string
    {
        return $this->state;
    }

    public function setState(string $state): void
    {
        $this->state = \mb_strtolower($state);
    }

    public function getAmount(): Amount
    {
        return $this->amount;
    }

    public function setAmount(Amount $amount): void
    {
        $this->amount = $amount;
    }

    public function getPaymentMode(): string
    {
        return $this->paymentMode;
    }

    public function setPaymentMode(string $paymentMode): void
    {
        $this->paymentMode = $paymentMode;
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

    public function getProtectionEligibility(): string
    {
        return $this->protectionEligibility;
    }

    public function setProtectionEligibility(string $protectionEligibility): void
    {
        $this->protectionEligibility = $protectionEligibility;
    }

    public function getProtectionEligibilityType(): string
    {
        return $this->protectionEligibilityType;
    }

    public function setProtectionEligibilityType(string $protectionEligibilityType): void
    {
        $this->protectionEligibilityType = $protectionEligibilityType;
    }

    public function getReceiptId(): string
    {
        return $this->receiptId;
    }

    public function setReceiptId(string $receiptId): void
    {
        $this->receiptId = $receiptId;
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
