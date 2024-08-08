<?php declare(strict_types=1);
/*
 * (c) shopware AG <info@shopware.com>
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Swag\PayPal\Checkout\SalesChannel\Struct\Fastlane\ProfileData;

use Shopware\Core\Framework\Log\Package;
use Swag\PayPal\Checkout\SalesChannel\Struct\Common\Address;
use Swag\PayPal\Checkout\SalesChannel\Struct\Common\Name;
use Swag\PayPal\Checkout\SalesChannel\Struct\Common\PhoneNumber;
use Swag\PayPal\Checkout\SalesChannel\Struct\SDKStruct;

#[Package('checkout')]
class ShippingAddress extends SDKStruct
{
    protected Address $address;

    protected Name $name;

    protected PhoneNumber $phoneNumber;

    public function getAddress(): Address
    {
        return $this->address;
    }

    public function setAddress(Address $address): void
    {
        $this->address = $address;
    }

    public function getName(): Name
    {
        return $this->name;
    }

    public function setName(Name $name): void
    {
        $this->name = $name;
    }

    public function getPhoneNumber(): PhoneNumber
    {
        return $this->phoneNumber;
    }

    public function setPhoneNumber(PhoneNumber $phoneNumber): void
    {
        $this->phoneNumber = $phoneNumber;
    }
}
