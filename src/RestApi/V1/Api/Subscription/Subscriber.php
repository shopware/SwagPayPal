<?php declare(strict_types=1);
/*
 * (c) shopware AG <info@shopware.com>
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Swag\PayPal\RestApi\V1\Api\Subscription;

use OpenApi\Annotations as OA;
use Swag\PayPal\RestApi\PayPalApiStruct;
use Swag\PayPal\RestApi\V1\Api\Subscription\Subscriber\Name;
use Swag\PayPal\RestApi\V1\Api\Subscription\Subscriber\ShippingAddress;

/**
 * @OA\Schema(schema="swag_paypal_v1_subscription_subscriber")
 * @codeCoverageIgnore
 * @experimental
 *
 * This class is experimental and not officially supported.
 * It is currently not used within the plugin itself. Use with caution.
 */
class Subscriber extends PayPalApiStruct
{
    /**
     * @deprecated tag:v4.0.0 - will be strongly typed
     *
     * @var Name
     * @OA\Property(ref="#/components/schemas/swag_paypal_v1_subscription_name")
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
     * @var ShippingAddress|null
     * @OA\Property(ref="#/components/schemas/swag_paypal_v1_subscription_shipping_address", nullable=true)
     */
    protected $shippingAddress;

    public function getName(): Name
    {
        return $this->name;
    }

    public function setName(Name $name): void
    {
        $this->name = $name;
    }

    public function getEmailAddress(): string
    {
        return $this->emailAddress;
    }

    public function setEmailAddress(string $emailAddress): void
    {
        $this->emailAddress = $emailAddress;
    }

    public function getPayerId(): string
    {
        return $this->payerId;
    }

    public function setPayerId(string $payerId): void
    {
        $this->payerId = $payerId;
    }

    public function getShippingAddress(): ?ShippingAddress
    {
        return $this->shippingAddress;
    }

    public function setShippingAddress(?ShippingAddress $shippingAddress): void
    {
        $this->shippingAddress = $shippingAddress;
    }
}
