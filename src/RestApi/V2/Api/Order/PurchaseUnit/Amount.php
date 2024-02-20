<?php declare(strict_types=1);
/*
 * (c) shopware AG <info@shopware.com>
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Swag\PayPal\RestApi\V2\Api\Order\PurchaseUnit;

use OpenApi\Attributes as OA;
use Shopware\Core\Framework\Log\Package;
use Swag\PayPal\RestApi\V2\Api\Common\Money;
use Swag\PayPal\RestApi\V2\Api\Order\PurchaseUnit\Amount\Breakdown;

#[OA\Schema(schema: 'swag_paypal_v2_order_purchase_unit_amount')]
#[Package('checkout')]
class Amount extends Money
{
    #[OA\Property(ref: Breakdown::class, nullable: true)]
    protected ?Breakdown $breakdown = null;

    public function getBreakdown(): ?Breakdown
    {
        return $this->breakdown;
    }

    public function setBreakdown(?Breakdown $breakdown): void
    {
        $this->breakdown = $breakdown;
    }
}
