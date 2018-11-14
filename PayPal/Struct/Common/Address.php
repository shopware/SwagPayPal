<?php declare(strict_types=1);
/**
 * (c) shopware AG <info@shopware.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace SwagPayPal\PayPal\Struct\Common;

class Address
{
    /**
     * @var string
     */
    private $line1;

    /**
     * @var string
     */
    private $line2;

    /**
     * @var string
     */
    private $city;

    /**
     * @var string
     */
    private $state;

    /**
     * @var string
     */
    private $postalCode;

    /**
     * @var string
     */
    private $countryCode;

    /**
     * @var string
     */
    private $phone;

    public function getLine1(): string
    {
        return $this->line1;
    }

    public function setLine1(string $line1): void
    {
        $this->line1 = $line1;
    }

    public function getLine2(): string
    {
        return $this->line2;
    }

    public function setLine2(string $line2): void
    {
        $this->line2 = $line2;
    }

    public function getCity(): string
    {
        return $this->city;
    }

    public function setCity(string $city): void
    {
        $this->city = $city;
    }

    public function getState(): string
    {
        return $this->state;
    }

    public function setState(string $state): void
    {
        $this->state = $state;
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

    public function getPhone(): string
    {
        return $this->phone;
    }

    public function setPhone(string $phone): void
    {
        $this->phone = $phone;
    }

    /**
     * @param array|null $data
     *
     * @return Address
     */
    public static function fromArray(array $data = null): Address
    {
        $result = new self();

        if ($data === null) {
            return $result;
        }

        if (array_key_exists('city', $data)) {
            $result->setCity($data['city']);
        }
        if (array_key_exists('country_code', $data)) {
            $result->setCountryCode($data['country_code']);
        }
        if (array_key_exists('line1', $data)) {
            $result->setLine1($data['line1']);
        }
        if (array_key_exists('line2', $data)) {
            $result->setLine2($data['line2']);
        }
        if (array_key_exists('postal_code', $data)) {
            $result->setPostalCode($data['postal_code']);
        }
        if (array_key_exists('state', $data)) {
            $result->setState($data['state']);
        }
        if (array_key_exists('phone', $data)) {
            $result->setPhone($data['phone']);
        }

        return $result;
    }

    public function toArray(): array
    {
        return [
            'city' => $this->getCity(),
            'country_code' => $this->getCountryCode(),
            'line1' => $this->getLine1(),
            'line2' => $this->getLine2(),
            'postal_code' => $this->getPostalCode(),
            'state' => $this->getState(),
            'phone' => $this->getPhone(),
        ];
    }
}
