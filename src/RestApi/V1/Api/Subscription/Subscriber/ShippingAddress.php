<?php
declare(strict_types=1);
/*
 * (c) shopware AG <info@shopware.com>
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Swag\PayPal\RestApi\V1\Api\Subscription\Subscriber;

use Swag\PayPal\RestApi\PayPalApiStruct;
use Swag\PayPal\RestApi\V1\Api\Subscription\Subscriber\ShippingAddress\Address;
use Swag\PayPal\RestApi\V1\Api\Subscription\Subscriber\ShippingAddress\Name;

/**
 * @codeCoverageIgnore
 * @experimental
 *
 * This class is experimental and not officially supported.
 * It is currently not used within the plugin itself. Use with caution.
 */
class ShippingAddress extends PayPalApiStruct
{
    /**
     * @deprecated tag:v4.0.0 - will be strongly typed
     *
     * @var Name|null
     */
    protected $name;

    /**
     * @deprecated tag:v4.0.0 - will be strongly typed
     *
     * @var Address|null
     */
    protected $address;

    public function getName(): ?Name
    {
        return $this->name;
    }

    public function setName(?Name $name): void
    {
        $this->name = $name;
    }

    public function getAddress(): ?Address
    {
        return $this->address;
    }

    public function setAddress(?Address $address): void
    {
        $this->address = $address;
    }
}
