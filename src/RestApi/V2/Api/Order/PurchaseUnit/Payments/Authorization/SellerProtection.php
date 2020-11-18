<?php declare(strict_types=1);
/*
 * (c) shopware AG <info@shopware.com>
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Swag\PayPal\RestApi\V2\Api\Order\PurchaseUnit\Payments\Authorization;

use Swag\PayPal\RestApi\PayPalApiStruct;

class SellerProtection extends PayPalApiStruct
{
    /**
     * @var string
     */
    protected $status;

    /**
     * @var string[]
     */
    protected $disputeCategories;

    public function getStatus(): string
    {
        return $this->status;
    }

    public function setStatus(string $status): void
    {
        $this->status = $status;
    }

    /**
     * @return string[]
     */
    public function getDisputeCategories(): array
    {
        return $this->disputeCategories;
    }

    /**
     * @param string[] $disputeCategories
     */
    public function setDisputeCategories(array $disputeCategories): void
    {
        $this->disputeCategories = $disputeCategories;
    }
}
