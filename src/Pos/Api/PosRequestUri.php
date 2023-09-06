<?php declare(strict_types=1);
/*
 * (c) shopware AG <info@shopware.com>
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Swag\PayPal\Pos\Api;

use Shopware\Core\Framework\Log\Package;

#[Package('checkout')]
final class PosRequestUri
{
    // Image
    public const IMAGE_RESOURCE_BULK = 'v2/images/organizations/self/products/bulk';

    // Inventory
    public const INVENTORY_RESOURCE_LOCATIONS = 'organizations/self/locations/';
    public const INVENTORY_RESOURCE_GET = 'organizations/self/inventory/locations/%s/';
    public const INVENTORY_RESOURCE_BULK = 'organizations/self/v2/inventory/bulk/';

    // Product
    public const PRODUCT_RESOURCE = 'organizations/self/products/';
    public const PRODUCT_RESOURCE_V2 = 'organizations/self/products/v2/';
    public const PRODUCT_RESOURCE_COUNT = 'organizations/self/products/v2/count/';

    // Subscription
    public const SUBSCRIPTION_RESOURCE = 'organizations/self/subscriptions/';
    public const SUBSCRIPTION_RESOURCE_DELETE = 'organizations/self/subscriptions/uuid/';

    // Token
    public const TOKEN_RESOURCE = 'token/';
    public const OAUTH_AUTHORIZATION = 'authorize';

    // User
    public const MERCHANT_INFORMATION = 'api/resources/organizations/self/';
    public const USER_IDENTIFICATION = 'users/me';

    private function __construct()
    {
    }
}
