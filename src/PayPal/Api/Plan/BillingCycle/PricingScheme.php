<?php declare(strict_types=1);
/*
 * (c) shopware AG <info@shopware.com>
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Swag\PayPal\PayPal\Api\Plan\BillingCycle;

use Swag\PayPal\PayPal\Api\Common\PayPalStruct;
use Swag\PayPal\PayPal\Api\Plan\BillingCycle\PricingScheme\FixedPrice;

class PricingScheme extends PayPalStruct
{
    /**
     * @var FixedPrice
     */
    protected $fixedPrice;

    public function getFixedPrice(): FixedPrice
    {
        return $this->fixedPrice;
    }

    public function setFixedPrice(FixedPrice $fixed_price): void
    {
        $this->fixedPrice = $fixed_price;
    }
}
