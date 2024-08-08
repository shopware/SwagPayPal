<?php declare(strict_types=1);
/*
 * (c) shopware AG <info@shopware.com>
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Swag\PayPal\Checkout\SalesChannel\Struct\Fastlane\ProfileData\Card\PaymentSource;

use Shopware\Core\Framework\Log\Package;
use Swag\PayPal\Checkout\SalesChannel\Struct\Common\Address;
use Swag\PayPal\Checkout\SalesChannel\Struct\SDKStruct;

#[Package('checkout')]
class Card extends SDKStruct
{
    protected Address $billingAddress;

    protected string $brand;

    protected string $expiry;

    protected string $lastDigits;

    protected string $name;

    public function getBillingAddress(): Address
    {
        return $this->billingAddress;
    }

    public function setBillingAddress(Address $billingAddress): void
    {
        $this->billingAddress = $billingAddress;
    }

    public function getBrand(): string
    {
        return $this->brand;
    }

    public function setBrand(string $brand): void
    {
        $this->brand = $brand;
    }

    public function getExpiry(): string
    {
        return $this->expiry;
    }

    public function setExpiry(string $expiry): void
    {
        $this->expiry = $expiry;
    }

    public function getLastDigits(): string
    {
        return $this->lastDigits;
    }

    public function setLastDigits(string $lastDigits): void
    {
        $this->lastDigits = $lastDigits;
    }

    public function getName(): string
    {
        return $this->name;
    }

    public function setName(string $name): void
    {
        $this->name = $name;
    }
}
