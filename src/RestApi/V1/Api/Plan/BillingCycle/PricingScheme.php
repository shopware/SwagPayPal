<?php declare(strict_types=1);
/*
 * (c) shopware AG <info@shopware.com>
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Swag\PayPal\RestApi\V1\Api\Plan\BillingCycle;

use Swag\PayPal\RestApi\PayPalApiStruct;
use Swag\PayPal\RestApi\V1\Api\Plan\BillingCycle\PricingScheme\FixedPrice;

/**
 * @codeCoverageIgnore
 * @experimental
 *
 * This class is experimental and not officially supported.
 * It is currently not used within the plugin itself. Use with caution.
 */
class PricingScheme extends PayPalApiStruct
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
