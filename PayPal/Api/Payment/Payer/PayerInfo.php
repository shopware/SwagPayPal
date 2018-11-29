<?php declare(strict_types=1);
/**
 * (c) shopware AG <info@shopware.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace SwagPayPal\PayPal\Api\Payment\Payer;

use SwagPayPal\PayPal\Api\Payment\Payer\PayerInfo\ShippingAddress;
use SwagPayPal\PayPal\Api\PayPalStruct;

class PayerInfo extends PayPalStruct
{
    /**
     * @var string
     */
    protected $payerId;
    /**
     * @var string
     */
    private $email;

    /**
     * @var string
     */
    private $firstName;

    /**
     * @var string
     */
    private $lastName;

    /**
     * @var string
     */
    private $phone;

    /**
     * @var ShippingAddress
     */
    private $shippingAddress;

    /**
     * @var string
     */
    private $countryCode;

    public function setPayerId(string $payerId): void
    {
        $this->payerId = $payerId;
    }

    protected function setEmail(string $email): void
    {
        $this->email = $email;
    }

    protected function setFirstName(string $firstName): void
    {
        $this->firstName = $firstName;
    }

    protected function setLastName(string $lastName): void
    {
        $this->lastName = $lastName;
    }

    protected function setPhone(string $phone): void
    {
        $this->phone = $phone;
    }

    protected function setShippingAddress(ShippingAddress $shippingAddress): void
    {
        $this->shippingAddress = $shippingAddress;
    }

    protected function setCountryCode(string $countryCode): void
    {
        $this->countryCode = $countryCode;
    }
}
