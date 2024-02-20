<?php declare(strict_types=1);
/*
 * (c) shopware AG <info@shopware.com>
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Swag\PayPal\RestApi\V2\Api\Order\PurchaseUnit\Payments;

use OpenApi\Attributes as OA;
use Shopware\Core\Framework\Log\Package;
use Swag\PayPal\RestApi\V2\Api\Order\PurchaseUnit\Payments\Common\SellerProtection;

#[OA\Schema(schema: 'swag_paypal_v2_order_purchase_unit_payments_authorization')]
#[Package('checkout')]
class Authorization extends Payment
{
    #[OA\Property(ref: SellerProtection::class)]
    protected SellerProtection $sellerProtection;

    #[OA\Property(type: 'string')]
    protected string $expirationTime;

    public function getSellerProtection(): SellerProtection
    {
        return $this->sellerProtection;
    }

    public function setSellerProtection(SellerProtection $sellerProtection): void
    {
        $this->sellerProtection = $sellerProtection;
    }

    public function getExpirationTime(): string
    {
        return $this->expirationTime;
    }

    public function setExpirationTime(string $expirationTime): void
    {
        $this->expirationTime = $expirationTime;
    }
}
