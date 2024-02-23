<?php declare(strict_types=1);
/*
 * (c) shopware AG <info@shopware.com>
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Swag\PayPal\RestApi\V1\Api\Payment\Payer;

use OpenApi\Attributes as OA;
use Shopware\Core\Framework\Log\Package;
use Swag\PayPal\RestApi\V1\Api\Common\Address;
use Swag\PayPal\RestApi\V1\Api\Payment\Transaction\ItemList\ShippingAddress;

#[OA\Schema(schema: 'swag_paypal_v1_payment_payer_payer_info')]
#[Package('checkout')]
class PayerInfo extends ExecutePayerInfo
{
    #[OA\Property(type: 'string')]
    protected string $email;

    #[OA\Property(type: 'string')]
    protected string $firstName;

    #[OA\Property(type: 'string')]
    protected string $lastName;

    #[OA\Property(ref: Address::class, nullable: true)]
    protected ?Address $billingAddress = null;

    #[OA\Property(ref: ShippingAddress::class)]
    protected ShippingAddress $shippingAddress;

    #[OA\Property(type: 'string')]
    protected string $phone;

    #[OA\Property(type: 'string')]
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

    public function getBillingAddress(): ?Address
    {
        return $this->billingAddress;
    }

    public function setBillingAddress(?Address $billingAddress): void
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
