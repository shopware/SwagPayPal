<?php declare(strict_types=1);
/*
 * (c) shopware AG <info@shopware.com>
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Swag\PayPal\Checkout\SalesChannel\Struct\Common;

use Shopware\Core\Framework\Log\Package;
use Shopware\Core\Framework\Struct\Struct;
use Swag\PayPal\Checkout\SalesChannel\Struct\SDKStruct;

#[Package('checkout')]
class PhoneNumber extends SDKStruct
{
    protected string $countryCode;

    protected string $nationalNumber;

    public function getCountryCode(): string
    {
        return $this->countryCode;
    }

    public function setCountryCode(string $countryCode): void
    {
        $this->countryCode = $countryCode;
    }

    public function getNationalNumber(): string
    {
        return $this->nationalNumber;
    }

    public function setNationalNumber(string $nationalNumber): void
    {
        $this->nationalNumber = $nationalNumber;
    }
}
