<?php declare(strict_types=1);
/*
 * (c) shopware AG <info@shopware.com>
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Swag\PayPal\AgentCommerce\Routing;

use Shopware\Core\Framework\Api\Context\ContextSource;
use Shopware\Core\Framework\Log\Package;

#[Package('checkout')]
final class PayPalAgentSourceGuard
{
    final public const SCOPE_CART = 'cart';
    final public const SCOPE_CHECKOUT = 'checkout';

    private function __construct()
    {
    }

    /**
     * @param string[] $scopes
     */
    public static function validForScopes(array $scopes, ContextSource $source): bool
    {
        if (!$source instanceof PayPalAgentSource) {
            return false;
        }

        foreach ($scopes as $scope) {
            if (!$source->hasScope($scope)) {
                return false;
            }
        }

        return !$source->isExpired();
    }
}
