<?php declare(strict_types=1);
/*
 * (c) shopware AG <info@shopware.com>
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Swag\PayPal\Test\Pos\Mock\Client\_fixtures;

class GetProductCountFixture
{
    public const PRODUCT_COUNT = 42;

    public static function get(): array
    {
        return [
            'productCount' => 42,
        ];
    }
}
