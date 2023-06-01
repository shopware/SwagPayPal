<?php declare(strict_types=1);
/*
 * (c) shopware AG <info@shopware.com>
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Swag\PayPal\RestApi\V3;

use Shopware\Core\Framework\Log\Package;

#[Package('checkout')]
final class RequestUriV3
{
    public const VAULT_TOKEN_RESOURCE = 'v3/vault/payment-tokens';

    private function __construct()
    {
    }
}
