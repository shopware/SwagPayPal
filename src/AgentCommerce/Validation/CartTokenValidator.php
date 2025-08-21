<?php declare(strict_types=1);
/*
 * (c) shopware AG <info@shopware.com>
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Swag\PayPal\AgentCommerce\Validation;

use Shopware\Core\Framework\Log\Package;
use Swag\PayPal\AgentCommerce\Exception\AgentException;

/**
 * @internal
 */
#[Package('checkout')]
class CartTokenValidator
{
    public static function validateCartToken(string $cartToken): string
    {
        if (!\preg_match('/^CART-(\w+)$/', $cartToken, $matches)) {
            throw AgentException::invalidCartId();
        }

        return $matches[1];
    }
}
