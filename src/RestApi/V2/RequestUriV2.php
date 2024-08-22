<?php declare(strict_types=1);
/*
 * (c) shopware AG <info@shopware.com>
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Swag\PayPal\RestApi\V2;

use Shopware\Core\Framework\Log\Package;

#[Package('checkout')]
final class RequestUriV2
{
    public const AUTHORIZATIONS_RESOURCE = 'v2/payments/authorizations';
    public const CAPTURES_RESOURCE = 'v2/payments/captures';
    public const ORDERS_RESOURCE = 'v2/checkout/orders';
    public const REFUNDS_RESOURCE = 'v2/payments/refunds';

    private function __construct()
    {
    }
}
