<?php declare(strict_types=1);
/*
 * (c) shopware AG <info@shopware.com>
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Swag\PayPal\RestApi\V1\Api\Disputes\Item\Extensions;

use Swag\PayPal\RestApi\PayPalApiStruct;
use Swag\PayPal\RestApi\V1\Api\Disputes\Item\Extensions\MerchandizeDisputeProperties\ProductDetails;
use Swag\PayPal\RestApi\V1\Api\Disputes\Item\Extensions\MerchandizeDisputeProperties\ServiceDetails;

class MerchandizeDisputeProperties extends PayPalApiStruct
{
    /**
     * @deprecated tag:v4.0.0 - will be strongly typed
     *
     * @var string
     */
    protected $issueType;

    /**
     * @deprecated tag:v4.0.0 - will be strongly typed
     *
     * @var ProductDetails
     */
    protected $productDetails;

    /**
     * @deprecated tag:v4.0.0 - will be strongly typed
     *
     * @var ServiceDetails
     */
    protected $serviceDetails;

    public function getIssueType(): string
    {
        return $this->issueType;
    }

    public function setIssueType(string $issueType): void
    {
        $this->issueType = $issueType;
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
}
