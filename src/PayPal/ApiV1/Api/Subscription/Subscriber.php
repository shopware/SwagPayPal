<?php declare(strict_types=1);
/*
 * (c) shopware AG <info@shopware.com>
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Swag\PayPal\PayPal\ApiV1\Api\Subscription;

use Swag\PayPal\PayPal\ApiV1\Api\Subscription\Subscriber\Name;
use Swag\PayPal\PayPal\ApiV1\Api\Subscription\Subscriber\ShippingAddress;
use Swag\PayPal\PayPal\PayPalApiStruct;

/**
 * @codeCoverageIgnore
 * @experimental
 *
 * This class is experimental and not officially supported.
 * It is currently not used within the plugin itself. Use with caution.
 */
class Subscriber extends PayPalApiStruct
{
    /**
     * @var Name
     */
    protected $name;

    /**
     * @var string
     */
    protected $emailAddress;

    /**
     * @var string
     */
    protected $payerId;

    /**
     * @var ShippingAddress|null
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
