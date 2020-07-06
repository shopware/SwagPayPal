<?php declare(strict_types=1);
/*
 * (c) shopware AG <info@shopware.com>
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Swag\PayPal\Test\IZettle\Mock\Client\_fixtures;

class DeleteProductsFixture
{
    /**
     * @var string[]|null
     */
    public static $lastDeletedUuids;

    public static function delete(string $query): ?array
    {
        self::$lastDeletedUuids = \explode('&', \str_replace('uuid=', '', $query));

        return null;
    }
}
