<?php declare(strict_types=1);
/*
 * (c) shopware AG <info@shopware.com>
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Swag\PayPal\Test\IZettle\Mock\Client\_fixtures;

use PHPUnit\Framework\TestCase;
use Swag\PayPal\IZettle\Api\Common\IZettleStruct;
use Swag\PayPal\IZettle\Api\Inventory\StartTracking;
use Swag\PayPal\Test\IZettle\ConstantsForTesting;

class StartInventoryTrackingFixture
{
    /**
     * @var bool
     */
    public static $called = false;

    public static function post(?IZettleStruct $startTracking): array
    {
        if ($startTracking === null) {
            throw new \Exception('Empty tracking started');
        }

        $expected = new StartTracking();
        $expected->setProductUuid(ConstantsForTesting::PRODUCT_B_ID_CONVERTED);

        TestCase::assertEquals($expected, $startTracking);
        self::$called = true;

        return [
            'locationUuid' => ConstantsForTesting::LOCATION_STORE,
            'variants' => [
                [
                    'locationUuid' => ConstantsForTesting::LOCATION_STORE,
                    'locationType' => 'STORE',
                    'productUuid' => ConstantsForTesting::PRODUCT_B_ID_CONVERTED,
                    'variantUuid' => ConstantsForTesting::PRODUCT_B_ID_VARIANT,
                    'balance' => '0',
                ],
            ],
        ];
    }
}
