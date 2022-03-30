<?php declare(strict_types=1);
/*
 * (c) shopware AG <info@shopware.com>
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Swag\PayPal\Storefront\Data\Struct;

use Swag\PayPal\Storefront\Data\Struct\ACDC\CardholderData;

class ACDCCheckoutData extends AbstractCheckoutData
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
