<?php declare(strict_types=1);
/*
 * (c) shopware AG <info@shopware.com>
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Swag\PayPal\RestApi\V1\Api\Disputes\Common;

use OpenApi\Annotations as OA;
use Swag\PayPal\RestApi\PayPalApiStruct;

/**
 * @OA\Schema(schema="swag_paypal_v1_disputes_common_product_details")
 */
abstract class ProductDetails extends PayPalApiStruct
{
    /**
     * @OA\Property(type="string")
     */
    protected string $productReceived;

    /**
     * @OA\Property(type="string")
     */
    protected string $productReceivedTime;

    /**
     * @var SubReason[]
     *
     * @OA\Property(type="array", items={"$ref": "#/components/schemas/swag_paypal_v1_disputes_common_sub_reason"})
     */
    protected array $subReasons;

    /**
     * @OA\Property(type="string")
     */
    protected string $purchaseUrl;

    /**
     * @OA\Property(ref="#/components/schemas/swag_paypal_v1_disputes_common_return_details")
     */
    protected ReturnDetails $returnDetails;

    public function getProductReceived(): string
    {
        return $this->productReceived;
    }

    public function setProductReceived(string $productReceived): void
    {
        $this->productReceived = $productReceived;
    }

    public function getProductReceivedTime(): string
    {
        return $this->productReceivedTime;
    }

    public function setProductReceivedTime(string $productReceivedTime): void
    {
        $this->productReceivedTime = $productReceivedTime;
    }

    /**
     * @return SubReason[]
     */
    public function getSubReasons(): array
    {
        return $this->subReasons;
    }

    /**
     * @param SubReason[] $subReasons
     */
    public function setSubReasons(array $subReasons): void
    {
        $this->subReasons = $subReasons;
    }

    public function getPurchaseUrl(): string
    {
        return $this->purchaseUrl;
    }

    public function setPurchaseUrl(string $purchaseUrl): void
    {
        $this->purchaseUrl = $purchaseUrl;
    }

    public function getReturnDetails(): ReturnDetails
    {
        return $this->returnDetails;
    }

    public function setReturnDetails(ReturnDetails $returnDetails): void
    {
        $this->returnDetails = $returnDetails;
    }
}
