<?php declare(strict_types=1);
/*
 * (c) shopware AG <info@shopware.com>
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Swag\PayPal\RestApi\V1\Api\Plan\BillingCycle;

use OpenApi\Annotations as OA;
use Shopware\Core\Framework\Log\Package;
use Swag\PayPal\RestApi\PayPalApiStruct;
use Swag\PayPal\RestApi\V1\Api\Common\Money;

/**
 * @OA\Schema(schema="swag_paypal_v1_plan_pricing_scheme")
 *
 * @codeCoverageIgnore
 *
 * @experimental
 *
 * This class is experimental and not officially supported.
 * It is currently not used within the plugin itself. Use with caution.
 */
#[Package('checkout')]
class PricingScheme extends PayPalApiStruct
{
    /**
     * @OA\Property(ref="#/components/schemas/swag_paypal_v1_common_money")
     */
    protected Money $fixedPrice;

    public function getFixedPrice(): Money
    {
        return $this->fixedPrice;
    }

    public function setFixedPrice(Money $fixed_price): void
    {
        $this->fixedPrice = $fixed_price;
    }
}
