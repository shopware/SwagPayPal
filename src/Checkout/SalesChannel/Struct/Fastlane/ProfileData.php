<?php declare(strict_types=1);
/*
 * (c) shopware AG <info@shopware.com>
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Swag\PayPal\Checkout\SalesChannel\Struct\Fastlane;

use Shopware\Core\Framework\Log\Package;
use Swag\PayPal\Checkout\SalesChannel\Struct\Common\Name;
use Swag\PayPal\Checkout\SalesChannel\Struct\Fastlane\ProfileData\Card;
use Swag\PayPal\Checkout\SalesChannel\Struct\Fastlane\ProfileData\ShippingAddress;
use Swag\PayPal\Checkout\SalesChannel\Struct\SDKStruct;

#[Package('checkout')]
class ProfileData extends SDKStruct
{
    protected ShippingAddress $shippingAddress;

    protected Name $name;

    protected Card $card;

    public function getShippingAddress(): ShippingAddress
    {
        return $this->shippingAddress;
    }

    public function setShippingAddress(ShippingAddress $shippingAddress): void
    {
        $this->shippingAddress = $shippingAddress;
    }

    public function getName(): Name
    {
        return $this->name;
    }

    public function setName(Name $name): void
    {
        $this->name = $name;
    }

    public function getCard(): Card
    {
        return $this->card;
    }

    public function setCard(Card $card): void
    {
        $this->card = $card;
    }
}
