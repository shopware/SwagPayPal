<?php declare(strict_types=1);
/*
 * (c) shopware AG <info@shopware.com>
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Swag\PayPal\Checkout\ACDC;

use Swag\PayPal\Checkout\ACDC\Struct\CardholderData;
use Swag\PayPal\Checkout\APM\APMCheckoutData;

class ACDCCheckoutFieldData extends APMCheckoutData
{
    protected CardholderData $cardholderData;

    public function getCardholderData(): CardholderData
    {
        return $this->cardholderData;
    }

    public function setCardholderData(CardholderData $cardholderData): void
    {
        $this->cardholderData = $cardholderData;
    }
}
