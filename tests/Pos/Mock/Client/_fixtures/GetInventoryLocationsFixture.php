<?php declare(strict_types=1);
/*
 * (c) shopware AG <info@shopware.com>
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Swag\PayPal\Test\Pos\Mock\Client\_fixtures;

use Swag\PayPal\Test\Pos\ConstantsForTesting;

/**
 * @internal
 */
class GetInventoryLocationsFixture
{
    public static function get(): array
    {
        return [
            [
                'uuid' => ConstantsForTesting::LOCATION_STORE,
                'type' => 'STORE',
                'name' => 'STORE',
                'description' => 'System generated',
                'default' => true,
            ],
            [
                'uuid' => ConstantsForTesting::LOCATION_BIN,
                'type' => 'BIN',
                'name' => 'BIN',
                'description' => 'System generated',
                'default' => false,
            ],
            [
                'uuid' => ConstantsForTesting::LOCATION_SUPPLIER,
                'type' => 'SUPPLIER',
                'name' => 'SUPPLIER',
                'description' => 'System generated',
                'default' => false,
            ],
            [
                'uuid' => ConstantsForTesting::LOCATION_SOLD,
                'type' => 'SOLD',
                'name' => 'SOLD',
                'description' => 'System generated',
                'default' => false,
            ],
        ];
    }
}
