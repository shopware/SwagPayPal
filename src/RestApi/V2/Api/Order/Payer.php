<?php declare(strict_types=1);
/*
 * (c) shopware AG <info@shopware.com>
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Swag\PayPal\RestApi\V2\Api\Order;

use OpenApi\Annotations as OA;
use Swag\PayPal\RestApi\PayPalApiStruct;
use Swag\PayPal\RestApi\V2\Api\Order\Payer\Address;
use Swag\PayPal\RestApi\V2\Api\Order\Payer\Name;
use Swag\PayPal\RestApi\V2\Api\Order\Payer\Phone;

/**
 * @OA\Schema(schema="swag_paypal_v2_order_payer")
 */
class Payer extends PayPalApiStruct
{
    /**
     * @deprecated tag:v4.0.0 - will be strongly typed
     *
     * @var Name
     * @OA\Property(ref="#/components/schemas/swag_paypal_v2_order_name")
     */
    protected $name;

    /**
     * @deprecated tag:v4.0.0 - will be strongly typed
     *
     * @var string
     * @OA\Property(type="string")
     */
    protected $emailAddress;

    /**
     * @deprecated tag:v4.0.0 - will be strongly typed
     *
     * @var string
     * @OA\Property(type="string")
     */
    protected $payerId;

    /**
     * @deprecated tag:v4.0.0 - will be strongly typed
     *
     * @var Phone|null
     * @OA\Property(ref="#/components/schemas/swag_paypal_v2_order_phone", nullable=true)
     */
    protected $phone;

    /**
     * @deprecated tag:v4.0.0 - will be strongly typed
     *
     * @var Address
     * @OA\Property(ref="#/components/schemas/swag_paypal_v2_common_address")
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

    public function getPhone(): ?Phone
    {
        return $this->phone;
    }

    public function setPhone(?Phone $phone): void
    {
        $this->phone = $phone;
    }
}
