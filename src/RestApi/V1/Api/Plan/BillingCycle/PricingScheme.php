<?php declare(strict_types=1);
/*
 * (c) shopware AG <info@shopware.com>
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Swag\PayPal\RestApi\V1\Api\Plan\BillingCycle;

use OpenApi\Attributes as OA;
use Shopware\Core\Framework\Log\Package;
use Swag\PayPal\RestApi\PayPalApiStruct;
use Swag\PayPal\RestApi\V1\Api\Common\Money;

/**
 * @codeCoverageIgnore
 *
 * @experimental
 *
 * This class is experimental and not officially supported.
 * It is currently not used within the plugin itself. Use with caution.
 */
#[OA\Schema(schema: 'swag_paypal_v1_plan_billing_cycle_pricing_scheme')]
#[Package('checkout')]
class PricingScheme extends PayPalApiStruct
{
    #[OA\Property(ref: Money::class)]
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
