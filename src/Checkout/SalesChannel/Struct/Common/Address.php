<?php declare(strict_types=1);
/*
 * (c) shopware AG <info@shopware.com>
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Swag\PayPal\Checkout\SalesChannel\Struct\Common;

use Shopware\Core\Framework\Log\Package;
use Swag\PayPal\Checkout\SalesChannel\Struct\SDKStruct;

#[Package('checkout')]
class Address extends SDKStruct
{
    protected string $addressLine1;

    protected ?string $addressLine2 = null;

    protected string $adminArea1;

    protected string $adminArea2;

    protected string $countryCode;

    protected string $postalCode;

    public function getAddressLine1(): string
    {
        return $this->addressLine1;
    }

    public function setAddressLine1(string $addressLine1): void
    {
        $this->addressLine1 = $addressLine1;
    }

    public function getAddressLine2(): ?string
    {
        return $this->addressLine2;
    }

    public function setAddressLine2(?string $addressLine2): void
    {
        $this->addressLine2 = $addressLine2;
    }

    public function getAdminArea1(): string
    {
        return $this->adminArea1;
    }

    public function setAdminArea1(string $adminArea1): void
    {
        $this->adminArea1 = $adminArea1;
    }

    public function getAdminArea2(): string
    {
        return $this->adminArea2;
    }

    public function setAdminArea2(string $adminArea2): void
    {
        $this->adminArea2 = $adminArea2;
    }

    public function getCountryCode(): string
    {
        return $this->countryCode;
    }

    public function setCountryCode(string $countryCode): void
    {
        $this->countryCode = $countryCode;
    }

    public function getPostalCode(): string
    {
        return $this->postalCode;
    }

    public function setPostalCode(string $postalCode): void
    {
        $this->postalCode = $postalCode;
    }
}
