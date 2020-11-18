<?php declare(strict_types=1);
/*
 * (c) shopware AG <info@shopware.com>
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Swag\PayPal\RestApi\V2\Api\Order\PurchaseUnit\Payments;

use Swag\PayPal\RestApi\V2\Api\Order\PurchaseUnit\Payments\Authorization\SellerProtection;

class Authorization extends Payment
{
    /**
     * @var SellerProtection
     */
    protected $sellerProtection;

    /**
     * @var string
     */
    protected $expirationTime;

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
