<?php declare(strict_types=1);
/*
 * (c) shopware AG <info@shopware.com>
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Swag\PayPal\AgentCommerce\Struct\V1;

use Shopware\Core\Framework\Log\Package;
use Swag\PayPal\RestApi\PayPalApiCollection;

/**
 * @experimental
 *
 * @extends PayPalApiCollection<CartItem>
 */
#[Package('checkout')]
class CartItemCollection extends PayPalApiCollection
{
    public static function getExpectedClass(): string
    {
        return CartItem::class;
    }
}
