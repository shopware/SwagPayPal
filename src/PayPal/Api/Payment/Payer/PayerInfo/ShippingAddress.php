<?php declare(strict_types=1);
/**
 * (c) shopware AG <info@shopware.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Swag\PayPal\PayPal\Api\Payment\Payer\PayerInfo;

use Swag\PayPal\PayPal\Api\Common\PayPalStruct;

class ShippingAddress extends PayPalStruct
{
    /**
     * @var string
     */
    private $recipientName;

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
     * @var string|null
     */
    private $phone;

    public function getLine1(): string
    {
        return $this->line1;
    }

    public function getRecipientName(): string
    {
        return $this->recipientName;
    }

    public function getCity(): string
    {
        return $this->city;
    }

    public function getState(): string
    {
        return $this->state;
    }

    public function getPostalCode(): string
    {
        return $this->postalCode;
    }

    public function getCountryCode(): string
    {
        return $this->countryCode;
    }

    public function getPhone(): ?string
    {
        return $this->phone;
    }

    protected function setRecipientName(string $recipientName): void
    {
        $this->recipientName = $recipientName;
    }

    protected function setLine1(string $line1): void
    {
        $this->line1 = $line1;
    }

    protected function setLine2(string $line2): void
    {
        $this->line2 = $line2;
    }

    protected function setCity(string $city): void
    {
        $this->city = $city;
    }

    protected function setState(string $state): void
    {
        $this->state = $state;
    }

    protected function setPostalCode(string $postalCode): void
    {
        $this->postalCode = $postalCode;
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
