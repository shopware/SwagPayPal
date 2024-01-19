<?php

declare(strict_types=1);
/*
 * (c) shopware AG <info@shopware.com>
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Swag\PayPal\Test\Pos\Mock\Client\_fixtures;

use Shopware\Core\Framework\Log\Package;

/**
 * @internal
 */
#[Package('checkout')]
class DeleteProductsFixture
{
    /**
     * @var string[]|null
     */
    public static ?array $deletedUuids = null;

    public static function delete(string $query): ?array
    {
        if (self::$deletedUuids === null) {
            self::$deletedUuids = [];
        }
        self::$deletedUuids = \array_merge(
            self::$deletedUuids,
            \explode('&', \str_replace('uuid=', '', $query))
        );

        return null;
    }
}
