<?php declare(strict_types=1);
/*
 * (c) shopware AG <info@shopware.com>
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Swag\PayPal\RestApi\V1\Api\Webhook;

use OpenApi\Annotations as OA;
use Swag\PayPal\RestApi\PayPalApiStruct;
use Swag\PayPal\RestApi\V1\Api\Webhook\Resource\Amount;
use Swag\PayPal\RestApi\V1\Api\Webhook\Resource\Link;
use Swag\PayPal\RestApi\V1\Api\Webhook\Resource\TransactionFee;

/**
 * @OA\Schema(schema="swag_paypal_v1_webhook_resource")
 */
class Resource extends PayPalApiStruct
{
    /**
     * @OA\Property(type="string")
     */
    protected string $id;

    /**
     * @OA\Property(type="string", nullable=true)
     */
    protected ?string $parentPayment = null;

    /**
     * @OA\Property(type="string", nullable=true)
     */
    protected ?string $billingAgreementId = null;

    /**
     * @OA\Property(type="string", nullable=true)
     */
    protected ?string $saleId = null;

    /**
     * @OA\Property(type="string", nullable=true)
     */
    protected ?string $refundReasonCode = null;

    /**
     * @OA\Property(type="string")
     */
    protected string $updateTime;

    /**
     * @OA\Property(ref="#/components/schemas/swag_paypal_v1_common_amount")
     */
    protected Amount $amount;

    /**
     * @OA\Property(type="string")
     */
    protected string $paymentMode;

    /**
     * @OA\Property(type="string")
     */
    protected string $createTime;

    /**
     * @OA\Property(type="string")
     */
    protected string $clearingTime;

    /**
     * @OA\Property(type="string")
     */
    protected string $protectionEligibilityType;

    /**
     * @OA\Property(type="string")
     */
    protected string $protectionEligibility;

    /**
     * @OA\Property(ref="#/components/schemas/swag_paypal_v1_common_value")
     */
    protected TransactionFee $transactionFee;

    /**
     * @OA\Property(type="string")
     */
    protected string $invoiceNumber;

    /**
     * @var Link[]
     * @OA\Property(type="array", items={"$ref": "#/components/schemas/swag_paypal_v1_common_link"})
     */
    protected array $links;

    /**
     * @OA\Property(type="string")
     */
    protected string $state;

    /**
     * @OA\Property(type="string", nullable=true)
     */
    protected ?string $merchantId = null;

    public function getId(): string
    {
        return $this->id;
    }

    public function setId(string $id): void
    {
        $this->id = $id;
    }

    public function getParentPayment(): ?string
    {
        return $this->parentPayment;
    }

    public function setParentPayment(?string $parentPayment): void
    {
        $this->parentPayment = $parentPayment;
    }

    public function getBillingAgreementId(): ?string
    {
        return $this->billingAgreementId;
    }

    public function setBillingAgreementId(?string $billingAgreementId): void
    {
        $this->billingAgreementId = $billingAgreementId;
    }

    public function getSaleId(): ?string
    {
        return $this->saleId;
    }

    public function setSaleId(?string $saleId): void
    {
        $this->saleId = $saleId;
    }

    public function getRefundReasonCode(): ?string
    {
        return $this->refundReasonCode;
    }

    public function setRefundReasonCode(?string $refundReasonCode): void
    {
        $this->refundReasonCode = $refundReasonCode;
    }

    public function getUpdateTime(): string
    {
        return $this->updateTime;
    }

    public function setUpdateTime(string $updateTime): void
    {
        $this->updateTime = $updateTime;
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

    public function getClearingTime(): string
    {
        return $this->clearingTime;
    }

    public function setClearingTime(string $clearingTime): void
    {
        $this->clearingTime = $clearingTime;
    }

    public function getProtectionEligibilityType(): string
    {
        return $this->protectionEligibilityType;
    }

    public function setProtectionEligibilityType(string $protectionEligibilityType): void
    {
        $this->protectionEligibilityType = $protectionEligibilityType;
    }

    public function getProtectionEligibility(): string
    {
        return $this->protectionEligibility;
    }

    public function setProtectionEligibility(string $protectionEligibility): void
    {
        $this->protectionEligibility = $protectionEligibility;
    }

    public function getTransactionFee(): TransactionFee
    {
        return $this->transactionFee;
    }

    public function setTransactionFee(TransactionFee $transactionFee): void
    {
        $this->transactionFee = $transactionFee;
    }

    public function getInvoiceNumber(): string
    {
        return $this->invoiceNumber;
    }

    public function setInvoiceNumber(string $invoiceNumber): void
    {
        $this->invoiceNumber = $invoiceNumber;
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

    public function getState(): string
    {
        return $this->state;
    }

    public function setState(string $state): void
    {
        $this->state = $state;
    }

    public function getMerchantId(): ?string
    {
        return $this->merchantId;
    }

    public function setMerchantId(?string $merchantId): void
    {
        $this->merchantId = $merchantId;
    }
}
