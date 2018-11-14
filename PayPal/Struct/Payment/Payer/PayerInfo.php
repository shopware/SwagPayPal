<?php declare(strict_types=1);
/**
 * (c) shopware AG <info@shopware.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace SwagPayPal\PayPal\Struct\Payment\Payer;

use SwagPayPal\PayPal\Struct\Common\Address;

class PayerInfo
{
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
    private $payerId;

    /**
     * @var string
     */
    private $phone;

    /**
     * @var string
     */
    private $countryCode;

    /**
     * @var Address
     */
    private $billingAddress;

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

    public function getPayerId(): string
    {
        return $this->payerId;
    }

    public function setPayerId(string $payerId): void
    {
        $this->payerId = $payerId;
    }

    public function getPhone(): string
    {
        return $this->phone;
    }

    public function setPhone(string $phone): void
    {
        $this->phone = $phone;
    }

    public function getCountryCode(): string
    {
        return $this->countryCode;
    }

    public function setCountryCode(string $countryCode): void
    {
        $this->countryCode = $countryCode;
    }

    public function getBillingAddress(): Address
    {
        return $this->billingAddress;
    }

    public function setBillingAddress(Address $billingAddress): void
    {
        $this->billingAddress = $billingAddress;
    }

    public static function fromArray(array $data = null): PayerInfo
    {
        $result = new self();

        if ($data === null) {
            return $result;
        }

        if (array_key_exists('country_code', $data)) {
            $result->setCountryCode($data['country_code']);
        }
        if (array_key_exists('email', $data)) {
            $result->setEmail($data['email']);
        }
        if (array_key_exists('first_name', $data)) {
            $result->setFirstName($data['first_name']);
        }
        if (array_key_exists('last_name', $data)) {
            $result->setLastName($data['last_name']);
        }
        if (array_key_exists('payer_id', $data)) {
            $result->setPayerId($data['payer_id']);
        }
        if (array_key_exists('phone', $data)) {
            $result->setPhone($data['phone']);
        }
        if (array_key_exists('shipping_address', $data)) {
            $result->setBillingAddress(Address::fromArray($data['shipping_address']));
        }

        return $result;
    }

    public function toArray(): array
    {
        $result = [
            'country_code' => $this->getCountryCode(),
            'email' => $this->getEmail(),
            'first_name' => $this->getFirstName(),
            'last_name' => $this->getLastName(),
            'payer_id' => $this->getPayerId(),
            'phone' => $this->getPhone(),
        ];

        if ($this->billingAddress !== null) {
            $result['billing_address'] = $this->billingAddress->toArray();
        }

        return $result;
    }
}
