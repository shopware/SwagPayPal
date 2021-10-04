<?php declare(strict_types=1);
/*
 * (c) shopware AG <info@shopware.com>
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Swag\PayPal\RestApi\V1\Api\Disputes\Item\Extensions\BillingDisputeProperties;

use OpenApi\Annotations as OA;
use Swag\PayPal\RestApi\PayPalApiStruct;
use Swag\PayPal\RestApi\V1\Api\Disputes\Item\Extensions\BillingDisputeProperties\CreditNotProcessed\AgreedRefundDetails;
use Swag\PayPal\RestApi\V1\Api\Disputes\Item\Extensions\BillingDisputeProperties\CreditNotProcessed\CancellationDetails;
use Swag\PayPal\RestApi\V1\Api\Disputes\Item\Extensions\BillingDisputeProperties\CreditNotProcessed\ExpectedRefund;
use Swag\PayPal\RestApi\V1\Api\Disputes\Item\Extensions\BillingDisputeProperties\CreditNotProcessed\ProductDetails;
use Swag\PayPal\RestApi\V1\Api\Disputes\Item\Extensions\BillingDisputeProperties\CreditNotProcessed\ServiceDetails;

/**
 * @OA\Schema(schema="swag_paypal_v1_disputes_extensions_cretid_not_processed")
 */
class CreditNotProcessed extends PayPalApiStruct
{
    /**
     * @OA\Property(type="string")
     */
    protected string $issueType;

    /**
     * @OA\Property(ref="#/components/schemas/swag_paypal_v1_common_money")
     */
    protected ExpectedRefund $expectedRefund;

    /**
     * @OA\Property(ref="#/components/schemas/swag_paypal_v1_disputes_extensions_cancellation_details")
     */
    protected CancellationDetails $cancellationDetails;

    /**
     * @OA\Property(ref="#/components/schemas/swag_paypal_v1_disputes_common_product_details")
     */
    protected ProductDetails $productDetails;

    /**
     * @OA\Property(ref="#/components/schemas/swag_paypal_v1_disputes_common_service_details")
     */
    protected ServiceDetails $serviceDetails;

    /**
     * @OA\Property(ref="#/components/schemas/swag_paypal_v1_disputes_extensions_aggred_refund_details")
     */
    protected AgreedRefundDetails $agreedRefundDetails;

    public function getIssueType(): string
    {
        return $this->issueType;
    }

    public function setIssueType(string $issueType): void
    {
        $this->issueType = $issueType;
    }

    public function getExpectedRefund(): ExpectedRefund
    {
        return $this->expectedRefund;
    }

    public function setExpectedRefund(ExpectedRefund $expectedRefund): void
    {
        $this->expectedRefund = $expectedRefund;
    }

    public function getCancellationDetails(): CancellationDetails
    {
        return $this->cancellationDetails;
    }

    public function setCancellationDetails(CancellationDetails $cancellationDetails): void
    {
        $this->cancellationDetails = $cancellationDetails;
    }

    public function getProductDetails(): ProductDetails
    {
        return $this->productDetails;
    }

    public function setProductDetails(ProductDetails $productDetails): void
    {
        $this->productDetails = $productDetails;
    }

    public function getServiceDetails(): ServiceDetails
    {
        return $this->serviceDetails;
    }

    public function setServiceDetails(ServiceDetails $serviceDetails): void
    {
        $this->serviceDetails = $serviceDetails;
    }

    public function getAgreedRefundDetails(): AgreedRefundDetails
    {
        return $this->agreedRefundDetails;
    }

    public function setAgreedRefundDetails(AgreedRefundDetails $agreedRefundDetails): void
    {
        $this->agreedRefundDetails = $agreedRefundDetails;
    }
}
