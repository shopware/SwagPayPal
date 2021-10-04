<?php declare(strict_types=1);
/*
 * (c) shopware AG <info@shopware.com>
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Swag\PayPal\RestApi\V1\Api\Payment\Payer;

use OpenApi\Annotations as OA;
use Swag\PayPal\RestApi\V1\Api\Payment\Payer\PayerInfo\BillingAddress;
use Swag\PayPal\RestApi\V1\Api\Payment\Payer\PayerInfo\ShippingAddress;

/**
 * @OA\Schema(schema="swag_paypal_v1_payment_payer_info")
 */
class PayerInfo extends ExecutePayerInfo
{
    /**
     * @OA\Property(type="string")
     */
    protected string $email;

    /**
     * @OA\Property(type="string")
     */
    protected string $firstName;

    /**
     * @OA\Property(type="string")
     */
    protected string $lastName;

    /**
     * @OA\Property(ref="#/components/schemas/swag_paypal_v1_common_address", nullable=true)
     */
    protected ?BillingAddress $billingAddress = null;

    /**
     * @OA\Property(ref="#/components/schemas/swag_paypal_v1_payment_payer_info_shipping_address")
     */
    protected ShippingAddress $shippingAddress;

    /**
     * @OA\Property(type="string")
     */
    protected string $phone;

    /**
     * @OA\Property(type="string")
     */
    protected string $countryCode;

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

    public function getBillingAddress(): ?BillingAddress
    {
        return $this->billingAddress;
    }

    public function setBillingAddress(?BillingAddress $billingAddress): void
    {
        $this->billingAddress = $billingAddress;
    }

    public function getShippingAddress(): ShippingAddress
    {
        return $this->shippingAddress;
    }

    public function setShippingAddress(ShippingAddress $shippingAddress): void
    {
        $this->shippingAddress = $shippingAddress;
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
}
