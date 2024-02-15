<?php declare(strict_types=1);
/*
 * (c) shopware AG <info@shopware.com>
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Swag\PayPal\Storefront\Data\Struct\ACDC;

use Shopware\Core\Framework\Log\Package;
use Shopware\Core\Framework\Struct\Struct;

/**
 * @deprecated tag:v10.0.0 - will be removed without replacement
 */
#[Package('checkout')]
class BillingAddress extends Struct
{
    protected string $streetAddress;

    protected ?string $extendedAddress;

    protected ?string $region;

    protected string $locality;

    protected string $postalCode;

    protected ?string $countryCodeAlpha2;

    public function getStreetAddress(): string
    {
        return $this->streetAddress;
    }

    public function setStreetAddress(string $streetAddress): void
    {
        $this->streetAddress = $streetAddress;
    }

    public function getExtendedAddress(): ?string
    {
        return $this->extendedAddress;
    }

    public function setExtendedAddress(?string $extendedAddress): void
    {
        $this->extendedAddress = $extendedAddress;
    }

    public function getRegion(): ?string
    {
        return $this->region;
    }

    public function setRegion(?string $region): void
    {
        $this->region = $region;
    }

    public function getLocality(): string
    {
        return $this->locality;
    }

    public function setLocality(string $locality): void
    {
        $this->locality = $locality;
    }

    public function getPostalCode(): string
    {
        return $this->postalCode;
    }

    public function setPostalCode(string $postalCode): void
    {
        $this->postalCode = $postalCode;
    }

    public function getCountryCodeAlpha2(): ?string
    {
        return $this->countryCodeAlpha2;
    }

    public function setCountryCodeAlpha2(?string $countryCodeAlpha2): void
    {
        $this->countryCodeAlpha2 = $countryCodeAlpha2;
    }
}
