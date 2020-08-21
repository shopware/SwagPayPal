<?php declare(strict_types=1);
/*
 * (c) shopware AG <info@shopware.com>
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Swag\PayPal\PayPal\ApiV2\Api\Order;

use Swag\PayPal\PayPal\ApiV2\Api\Order\Payer\Address;
use Swag\PayPal\PayPal\ApiV2\Api\Order\Payer\Name;
use Swag\PayPal\PayPal\ApiV2\Api\Order\Payer\Phone;
use Swag\PayPal\PayPal\PayPalApiStruct;

class Payer extends PayPalApiStruct
{
    /**
     * @var Name
     */
    protected $name;

    /**
     * @var string
     */
    protected $emailAddress;

    /**
     * @var string
     */
    protected $payerId;

    /**
     * @var Phone
     */
    protected $phone;

    /**
     * @var Address
     */
    protected $address;

    public function getEmailAddress(): string
    {
        return $this->emailAddress;
    }

    public function setEmailAddress(string $emailAddress): void
    {
        $this->emailAddress = $emailAddress;
    }

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

    public function getPayerId(): string
    {
        return $this->payerId;
    }

    public function setPayerId(string $payerId): void
    {
        $this->payerId = $payerId;
    }

    public function getPhone(): Phone
    {
        return $this->phone;
    }

    public function setPhone(Phone $phone): void
    {
        $this->phone = $phone;
    }
}
