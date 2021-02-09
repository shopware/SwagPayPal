<?php declare(strict_types=1);
/*
 * (c) shopware AG <info@shopware.com>
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Swag\PayPal\RestApi\V1\Api\Disputes\Common;

use Swag\PayPal\RestApi\PayPalApiStruct;

abstract class ProductDetails extends PayPalApiStruct
{
    /**
     * @var string
     */
    protected $productReceived;

    /**
     * @var string
     */
    protected $productReceivedTime;

    /**
     * @var SubReason[]
     */
    protected $subReasons;

    /**
     * @var string
     */
    protected $purchaseUrl;

    /**
     * @var ReturnDetails
     */
    protected $returnDetails;

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
