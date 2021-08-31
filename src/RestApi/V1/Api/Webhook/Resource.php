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
     * @deprecated tag:v4.0.0 - will be strongly typed
     *
     * @var string
     * @OA\Property(type="string")
     */
    protected $id;

    /**
     * @deprecated tag:v4.0.0 - will be strongly typed
     *
     * @var string|null
     * @OA\Property(type="string", nullable=true)
     */
    protected $parentPayment;

    /**
     * @deprecated tag:v4.0.0 - will be strongly typed
     *
     * @var string|null
     * @OA\Property(type="string", nullable=true)
     */
    protected $billingAgreementId;

    /**
     * @deprecated tag:v4.0.0 - will be strongly typed
     *
     * @var string|null
     * @OA\Property(type="string", nullable=true)
     */
    protected $saleId;

    /**
     * @deprecated tag:v4.0.0 - will be strongly typed
     *
     * @var string|null
     * @OA\Property(type="string", nullable=true)
     */
    protected $refundReasonCode;

    /**
     * @deprecated tag:v4.0.0 - will be strongly typed
     *
     * @var string
     * @OA\Property(type="string")
     */
    protected $updateTime;

    /**
     * @deprecated tag:v4.0.0 - will be strongly typed
     *
     * @var Amount
     * @OA\Property(ref="#/components/schemas/swag_paypal_v1_common_amount")
     */
    protected $amount;

    /**
     * @deprecated tag:v4.0.0 - will be strongly typed
     *
     * @var string
     * @OA\Property(type="string")
     */
    protected $paymentMode;

    /**
     * @deprecated tag:v4.0.0 - will be strongly typed
     *
     * @var string
     * @OA\Property(type="string")
     */
    protected $createTime;

    /**
     * @deprecated tag:v4.0.0 - will be strongly typed
     *
     * @var string
     * @OA\Property(type="string")
     */
    protected $clearingTime;

    /**
     * @deprecated tag:v4.0.0 - will be strongly typed
     *
     * @var string
     * @OA\Property(type="string")
     */
    protected $protectionEligibilityType;

    /**
     * @deprecated tag:v4.0.0 - will be strongly typed
     *
     * @var string
     * @OA\Property(type="string")
     */
    protected $protectionEligibility;

    /**
     * @deprecated tag:v4.0.0 - will be strongly typed
     *
     * @var TransactionFee
     * @OA\Property(ref="#/components/schemas/swag_paypal_v1_common_value")
     */
    protected $transactionFee;

    /**
     * @deprecated tag:v4.0.0 - will be strongly typed
     *
     * @var string
     * @OA\Property(type="string")
     */
    protected $invoiceNumber;

    /**
     * @deprecated tag:v4.0.0 - will be strongly typed
     *
     * @var Link[]
     * @OA\Property(type="array", items={"$ref": "#/components/schemas/swag_paypal_v1_common_link"})
     */
    protected $links;

    /**
     * @deprecated tag:v4.0.0 - will be strongly typed
     *
     * @var string
     * @OA\Property(type="string")
     */
    protected $state;

    /**
     * @deprecated tag:v4.0.0 - will be strongly typed
     *
     * @var string|null
     * @OA\Property(type="string", nullable=true)
     */
    protected $merchantId;

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
