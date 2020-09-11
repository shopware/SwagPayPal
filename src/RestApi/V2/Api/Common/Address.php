<?php declare(strict_types=1);
/*
 * (c) shopware AG <info@shopware.com>
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Swag\PayPal\RestApi\V2\Api\Common;

use Swag\PayPal\RestApi\PayPalApiStruct;

abstract class Address extends PayPalApiStruct
{
    /**
     * The first line of the address. For example, number or street. For example, 173 Drury Lane.
     * Required for data entry and compliance and risk checks. Must contain the full address.
     *
     * @var string
     */
    protected $addressLine_1;

    /**
     * The second line of the address. For example, suite or apartment number.
     *
     * @var string
     */
    protected $addressLine_2;

    /**
     * A city, town, or village. Smaller than $adminArea1
     *
     * @var string
     */
    protected $adminArea_2;

    /**
     * The highest level sub-division in a country, which is usually a province, state, or ISO-3166-2 subdivision.
     * Format for postal delivery. For example, CA and not California.
     *
     * @var string
     */
    protected $adminArea_1;

    /**
     * @var string
     */
    protected $postalCode;

    /**
     * @var string
     */
    protected $countryCode;

    public function getAddressLine1(): string
    {
        return $this->addressLine_1;
    }

    public function setAddressLine1(string $addressLine_1): void
    {
        $this->addressLine_1 = $addressLine_1;
    }

    public function getAddressLine2(): string
    {
        return $this->addressLine_2;
    }

    public function setAddressLine2(string $addressLine_2): void
    {
        $this->addressLine_2 = $addressLine_2;
    }

    public function getAdminArea2(): string
    {
        return $this->adminArea_2;
    }

    public function setAdminArea2(string $adminArea_2): void
    {
        $this->adminArea_2 = $adminArea_2;
    }

    public function getAdminArea1(): string
    {
        return $this->adminArea_1;
    }

    public function setAdminArea1(string $adminArea_1): void
    {
        $this->adminArea_1 = $adminArea_1;
    }

    public function getPostalCode(): string
    {
        return $this->postalCode;
    }

    public function setPostalCode(string $postalCode): void
    {
        $this->postalCode = $postalCode;
    }

    public function getCountryCode(): string
    {
        return $this->countryCode;
    }

    public function setCountryCode(string $countryCode): void
    {
        $this->countryCode = $countryCode;
    }
}
