<?php
declare(strict_types=1);
/*
 * (c) shopware AG <info@shopware.com>
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Swag\PayPal\PayPal\Api\Subscription\Subscriber;

use Swag\PayPal\PayPal\Api\Common\PayPalStruct;
use Swag\PayPal\PayPal\Api\Subscription\Subscriber\ShippingAddress\Address;
use Swag\PayPal\PayPal\Api\Subscription\Subscriber\ShippingAddress\Name as ShippingAddressName;

/**
 * @codeCoverageIgnore
 * @experimental
 *
 * This class is experimental and not officially supported.
 * It is currently not used within the plugin itself. Use with caution.
 */
class ShippingAddress extends PayPalStruct
{
    /**
     * @var ShippingAddressName|null
     */
    protected $name;

    /**
     * @var Address|null
     */
    protected $address;

    public function getName(): ?ShippingAddressName
    {
        return $this->name;
    }

    public function setName(?ShippingAddressName $name): void
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
