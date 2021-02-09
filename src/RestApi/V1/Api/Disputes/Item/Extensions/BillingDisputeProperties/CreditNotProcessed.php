<?php declare(strict_types=1);
/*
 * (c) shopware AG <info@shopware.com>
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Swag\PayPal\RestApi\V1\Api\Disputes\Item\Extensions\BillingDisputeProperties;

use Swag\PayPal\RestApi\PayPalApiStruct;
use Swag\PayPal\RestApi\V1\Api\Disputes\Item\Extensions\BillingDisputeProperties\CreditNotProcessed\AgreedRefundDetails;
use Swag\PayPal\RestApi\V1\Api\Disputes\Item\Extensions\BillingDisputeProperties\CreditNotProcessed\CancellationDetails;
use Swag\PayPal\RestApi\V1\Api\Disputes\Item\Extensions\BillingDisputeProperties\CreditNotProcessed\ExpectedRefund;
use Swag\PayPal\RestApi\V1\Api\Disputes\Item\Extensions\BillingDisputeProperties\CreditNotProcessed\ProductDetails;
use Swag\PayPal\RestApi\V1\Api\Disputes\Item\Extensions\BillingDisputeProperties\CreditNotProcessed\ServiceDetails;

class CreditNotProcessed extends PayPalApiStruct
{
    /**
     * @var string
     */
    protected $issueType;

    /**
     * @var ExpectedRefund
     */
    protected $expectedRefund;

    /**
     * @var CancellationDetails
     */
    protected $cancellationDetails;

    /**
     * @var ProductDetails
     */
    protected $productDetails;

    /**
     * @var ServiceDetails
     */
    protected $serviceDetails;

    /**
     * @var AgreedRefundDetails
     */
    protected $agreedRefundDetails;

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
