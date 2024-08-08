<?php declare(strict_types=1);
/*
 * (c) shopware AG <info@shopware.com>
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Swag\PayPal\Checkout\SalesChannel\Struct\Fastlane\ProfileData\Card;

use Shopware\Core\Framework\Log\Package;
use Swag\PayPal\Checkout\SalesChannel\Struct\Fastlane\ProfileData\Card\PaymentSource\Card;
use Swag\PayPal\Checkout\SalesChannel\Struct\SDKStruct;

#[Package('checkout')]
class PaymentSource extends SDKStruct
{
    protected Card $card;

    public function getCard(): Card
    {
        return $this->card;
    }

    public function setCard(Card $card): void
    {
        $this->card = $card;
    }
}
