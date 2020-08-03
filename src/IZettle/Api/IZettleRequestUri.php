<?php declare(strict_types=1);
/*
 * (c) shopware AG <info@shopware.com>
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Swag\PayPal\IZettle\Api;

final class IZettleRequestUri
{
    public const TOKEN_RESOURCE = 'token/';
    public const PRODUCT_RESOURCE = 'organizations/self/products/';
    public const PRODUCT_RESOURCE_V2 = 'organizations/self/products/v2/';
    public const INVENTORY_RESOURCE_LOCATIONS = 'organizations/self/locations/';
    public const INVENTORY_RESOURCE_GET = 'organizations/self/inventory/locations/%s/';
    public const INVENTORY_RESOURCE_BULK = 'organizations/self/v2/inventory/bulk/';
    public const IMAGE_RESOURCE_BULK = 'v2/images/organizations/self/products/bulk';
    public const MERCHANT_INFORMATION = 'api/resources/organizations/self/';

    private function __construct()
    {
    }
}
