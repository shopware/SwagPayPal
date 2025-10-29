<?php declare(strict_types=1);
/*
 * (c) shopware AG <info@shopware.com>
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Swag\PayPal\AgentCommerce\Struct\V1;

use OpenApi\Attributes as OA;
use Shopware\Core\Framework\Log\Package;

/**
 * @experimental
 */
#[Package('checkout')]
#[OA\Schema(
    schema: 'paypal_agentic_commerce_v1_shipping_address',
    required: ['countryCode']
)]
class ShippingAddress extends Address
{
}
