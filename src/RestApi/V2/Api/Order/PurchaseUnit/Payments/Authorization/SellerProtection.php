<?php declare(strict_types=1);
/*
 * (c) shopware AG <info@shopware.com>
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Swag\PayPal\RestApi\V2\Api\Order\PurchaseUnit\Payments\Authorization;

use OpenApi\Attributes as OA;
use Shopware\Core\Framework\Log\Package;
use Swag\PayPal\RestApi\PayPalApiStruct;

#[OA\Schema(schema: 'swag_paypal_v2_order_purchase_unit_payments_authorization_seller_protection')]
#[Package('checkout')]
class SellerProtection extends PayPalApiStruct
{
    #[OA\Property(type: 'string')]
    protected string $status;

    /**
     * @var string[]
     */
    #[OA\Property(type: 'array', items: new OA\Items(type: 'string'))]
    protected array $disputeCategories;

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
