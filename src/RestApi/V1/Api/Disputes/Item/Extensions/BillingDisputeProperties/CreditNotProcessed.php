<?php declare(strict_types=1);
/*
 * (c) shopware AG <info@shopware.com>
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Swag\PayPal\RestApi\V1\Api\Disputes\Item\Extensions\BillingDisputeProperties;

use OpenApi\Attributes as OA;
use Shopware\Core\Framework\Log\Package;
use Swag\PayPal\RestApi\PayPalApiStruct;
use Swag\PayPal\RestApi\V1\Api\Common\Money;
use Swag\PayPal\RestApi\V1\Api\Disputes\Common\ProductDetails;
use Swag\PayPal\RestApi\V1\Api\Disputes\Common\ServiceDetails;
use Swag\PayPal\RestApi\V1\Api\Disputes\Item\Extensions\BillingDisputeProperties\Common\AgreedRefundDetails;
use Swag\PayPal\RestApi\V1\Api\Disputes\Item\Extensions\BillingDisputeProperties\Common\CancellationDetails;

#[OA\Schema(schema: 'swag_paypal_v1_disputes_item_extensions_billing_dispute_properties_credit_not_processed')]
#[Package('checkout')]
class CreditNotProcessed extends PayPalApiStruct
{
    #[OA\Property(type: 'string')]
    protected string $issueType;

    #[OA\Property(ref: Money::class)]
    protected Money $expectedRefund;

    #[OA\Property(ref: CancellationDetails::class)]
    protected CancellationDetails $cancellationDetails;

    #[OA\Property(ref: ProductDetails::class)]
    protected ProductDetails $productDetails;

    #[OA\Property(ref: ServiceDetails::class)]
    protected ServiceDetails $serviceDetails;

    #[OA\Property(ref: AgreedRefundDetails::class)]
    protected AgreedRefundDetails $agreedRefundDetails;

    public function getIssueType(): string
    {
        return $this->issueType;
    }

    public function setIssueType(string $issueType): void
    {
        $this->issueType = $issueType;
    }

    public function getExpectedRefund(): Money
    {
        return $this->expectedRefund;
    }

    public function setExpectedRefund(Money $expectedRefund): void
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
