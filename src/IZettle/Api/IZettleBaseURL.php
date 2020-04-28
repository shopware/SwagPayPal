<?php declare(strict_types=1);
/*
 * (c) shopware AG <info@shopware.com>
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Swag\PayPal\IZettle\Api;

final class IZettleBaseURL
{
    public const OAUTH = 'https://oauth.izettle.com/';
    public const PRODUCTS = 'https://products.izettle.com/';

    private function __construct()
    {
    }
}
