<?php declare(strict_types=1);
/*
 * (c) shopware AG <info@shopware.com>
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Swag\PayPal\Test\IZettle\Mock\Client\_fixtures;

use PHPUnit\Framework\TestCase;
use Swag\PayPal\IZettle\Api\Common\IZettleStruct;
use Swag\PayPal\IZettle\Api\Inventory\Changes;
use Swag\PayPal\IZettle\Api\Inventory\Changes\Change;
use Swag\PayPal\Test\IZettle\ConstantsForTesting;

class ChangeInventoryFixture
{
    /**
     * @var bool
     */
    public static $called = false;

    public static function put(IZettleStruct $changes): array
    {
        $expected = new Changes();
        $expected->setReturnBalanceForLocationUuid(ConstantsForTesting::LOCATION_STORE);
        $change = new Change();
        $change->setProductUuid(ConstantsForTesting::PRODUCT_B_ID_CONVERTED);
        $change->setVariantUuid(ConstantsForTesting::PRODUCT_B_ID_VARIANT);
        $change->setFromLocationUuid(ConstantsForTesting::LOCATION_SUPPLIER);
        $change->setToLocationUuid(ConstantsForTesting::LOCATION_STORE);
        $change->setChange(2);
        $expected->addChange($change);
        $change = new Change();
        $change->setProductUuid(ConstantsForTesting::PRODUCT_D_ID_CONVERTED);
        $change->setVariantUuid(ConstantsForTesting::PRODUCT_D_ID_VARIANT);
        $change->setFromLocationUuid(ConstantsForTesting::LOCATION_STORE);
        $change->setToLocationUuid(ConstantsForTesting::LOCATION_BIN);
        $change->setChange(1);
        $expected->addChange($change);
        $change = new Change();
        $change->setProductUuid(ConstantsForTesting::PRODUCT_E_ID_CONVERTED);
        $change->setVariantUuid(ConstantsForTesting::PRODUCT_E_ID_VARIANT);
        $change->setFromLocationUuid(ConstantsForTesting::LOCATION_STORE);
        $change->setToLocationUuid(ConstantsForTesting::LOCATION_BIN);
        $change->setChange(1);
        $expected->addChange($change);

        TestCase::assertEquals($expected, $changes);
        self::$called = true;

        return [
            'locationUuid' => ConstantsForTesting::LOCATION_STORE,
            'variants' => [
                [
                    'locationUuid' => ConstantsForTesting::LOCATION_STORE,
                    'locationType' => 'STORE',
                    'productUuid' => ConstantsForTesting::PRODUCT_B_ID_CONVERTED,
                    'variantUuid' => ConstantsForTesting::PRODUCT_B_ID_VARIANT,
                    'balance' => '2',
                ],
                [
                    'locationUuid' => ConstantsForTesting::LOCATION_STORE,
                    'locationType' => 'STORE',
                    'productUuid' => ConstantsForTesting::PRODUCT_D_ID_CONVERTED,
                    'variantUuid' => ConstantsForTesting::PRODUCT_D_ID_VARIANT,
                    'balance' => '2',
                ],
                [
                    'locationUuid' => ConstantsForTesting::LOCATION_STORE,
                    'locationType' => 'STORE',
                    'productUuid' => ConstantsForTesting::PRODUCT_E_ID_CONVERTED,
                    'variantUuid' => ConstantsForTesting::PRODUCT_E_ID_VARIANT,
                    'balance' => '2',
                ],
            ],
        ];
    }
}
