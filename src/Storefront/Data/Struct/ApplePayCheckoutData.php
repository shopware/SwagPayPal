<?php declare(strict_types=1);
/*
 * (c) shopware AG <info@shopware.com>
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Swag\PayPal\Storefront\Data\Struct;

use Shopware\Core\Framework\Log\Package;

#[Package('checkout')]
class ApplePayCheckoutData extends AbstractCheckoutData
{
    protected string $totalPrice;

    protected string $brandName;

    protected array $billingAddress;

    public function getTotalPrice(): string
    {
        return $this->totalPrice;
    }

    public function setTotalPrice(string $totalPrice): void
    {
        $this->totalPrice = $totalPrice;
    }

    public function getBrandName(): string
    {
        return $this->brandName;
    }

    public function setBrandName(string $brandName): void
    {
        $this->brandName = $brandName;
    }

    public function getBillingAddress(): array
    {
        return $this->billingAddress;
    }

    public function setBillingAddress(array $billingAddress): void
    {
        $this->billingAddress = $billingAddress;
    }
}
