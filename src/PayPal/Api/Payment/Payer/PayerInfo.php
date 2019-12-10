<?php declare(strict_types=1);
/*
 * (c) shopware AG <info@shopware.com>
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Swag\PayPal\PayPal\Api\Payment\Payer;

use Swag\PayPal\PayPal\Api\Payment\Payer\PayerInfo\BillingAddress;
use Swag\PayPal\PayPal\Api\Payment\Payer\PayerInfo\ShippingAddress;

class PayerInfo extends ExecutePayerInfo
{
    /**
     * @var string
     */
    protected $email;

    /**
     * @var string
     */
    protected $firstName;

    /**
     * @var string
     */
    protected $lastName;

    /**
     * @var BillingAddress|null
     */
    protected $billingAddress;

    /**
     * @var ShippingAddress
     */
    private $shippingAddress;

    /**
     * @var string
     */
    private $phone;

    /**
     * @var string
     */
    private $countryCode;

    public function getEmail(): string
    {
        return $this->email;
    }

    public function setEmail(string $email): void
    {
        $this->email = $email;
    }

    public function getFirstName(): string
    {
        return $this->firstName;
    }

    public function setFirstName(string $firstName): void
    {
        $this->firstName = $firstName;
    }

    public function getLastName(): string
    {
        return $this->lastName;
    }

    public function setLastName(string $lastName): void
    {
        $this->lastName = $lastName;
    }

    public function getShippingAddress(): ShippingAddress
    {
        return $this->shippingAddress;
    }

    public function getBillingAddress(): ?BillingAddress
    {
        return $this->billingAddress;
    }

    public function setBillingAddress(BillingAddress $billingAddress): void
    {
        $this->billingAddress = $billingAddress;
    }

    protected function setShippingAddress(ShippingAddress $shippingAddress): void
    {
        $this->shippingAddress = $shippingAddress;
    }

    protected function setCountryCode(string $countryCode): void
    {
        $this->countryCode = $countryCode;
    }

    protected function setPhone(string $phone): void
    {
        $this->phone = $phone;
    }
}
