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

    private function __construct()
    {
    }
}
