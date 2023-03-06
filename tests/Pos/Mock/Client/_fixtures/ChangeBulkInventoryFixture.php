<?php declare(strict_types=1);
/*
 * (c) shopware AG <info@shopware.com>
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Swag\PayPal\Test\Pos\Mock\Client\_fixtures;

use PHPUnit\Framework\TestCase;
use Swag\PayPal\Pos\Api\Common\PosStruct;
use Swag\PayPal\Pos\Api\Inventory\BulkChanges;
use Swag\PayPal\Pos\Api\Inventory\BulkChanges\ProductChange;
use Swag\PayPal\Pos\Api\Inventory\BulkChanges\ProductChange\VariantChange;
use Swag\PayPal\Test\Pos\ConstantsForTesting;

/**
 * @internal
 */
class ChangeBulkInventoryFixture
{
    public static bool $called = false;

    public static function post(?PosStruct $changes): array
    {
        TestCase::assertNotNull($changes);

        $expected = new BulkChanges();
        $expected->setReturnBalanceForLocationUuid(ConstantsForTesting::LOCATION_STORE);
        $variantChange = new VariantChange();
        $variantChange->setProductUuid(ConstantsForTesting::PRODUCT_B_ID_CONVERTED);
        $variantChange->setVariantUuid(ConstantsForTesting::PRODUCT_B_ID_VARIANT);
        $variantChange->setFromLocationUuid(ConstantsForTesting::LOCATION_SUPPLIER);
        $variantChange->setToLocationUuid(ConstantsForTesting::LOCATION_STORE);
        $variantChange->setChange(2);
        $productChange = new ProductChange();
        $productChange->setTrackingStatusChange(ProductChange::TRACKING_START);
        $productChange->setProductUuid(ConstantsForTesting::PRODUCT_B_ID_CONVERTED);
        $productChange->setVariantChanges([$variantChange]);
        $expected->addProductChange($productChange);
        $variantChange = new VariantChange();
        $variantChange->setProductUuid(ConstantsForTesting::PRODUCT_D_ID_CONVERTED);
        $variantChange->setVariantUuid(ConstantsForTesting::PRODUCT_D_ID_VARIANT);
        $variantChange->setFromLocationUuid(ConstantsForTesting::LOCATION_STORE);
        $variantChange->setToLocationUuid(ConstantsForTesting::LOCATION_BIN);
        $variantChange->setChange(1);
        $productChange = new ProductChange();
        $productChange->setTrackingStatusChange(ProductChange::TRACKING_NOCHANGE);
        $productChange->setProductUuid(ConstantsForTesting::PRODUCT_D_ID_CONVERTED);
        $productChange->setVariantChanges([$variantChange]);
        $expected->addProductChange($productChange);
        $variantChange = new VariantChange();
        $variantChange->setProductUuid(ConstantsForTesting::PRODUCT_E_ID_CONVERTED);
        $variantChange->setVariantUuid(ConstantsForTesting::PRODUCT_E_ID_VARIANT);
        $variantChange->setFromLocationUuid(ConstantsForTesting::LOCATION_STORE);
        $variantChange->setToLocationUuid(ConstantsForTesting::LOCATION_BIN);
        $variantChange->setChange(1);
        $productChange = new ProductChange();
        $productChange->setTrackingStatusChange(ProductChange::TRACKING_NOCHANGE);
        $productChange->setProductUuid(ConstantsForTesting::PRODUCT_E_ID_CONVERTED);
        $productChange->setVariantChanges([$variantChange]);
        $expected->addProductChange($productChange);
        $variantChange = new VariantChange();
        $variantChange->setProductUuid(ConstantsForTesting::PRODUCT_G_ID_CONVERTED);
        $variantChange->setVariantUuid(ConstantsForTesting::PRODUCT_G_ID_VARIANT);
        $variantChange->setFromLocationUuid(ConstantsForTesting::LOCATION_SUPPLIER);
        $variantChange->setToLocationUuid(ConstantsForTesting::LOCATION_STORE);
        $variantChange->setChange(2);
        $productChange = new ProductChange();
        $productChange->setTrackingStatusChange(ProductChange::TRACKING_START);
        $productChange->setProductUuid(ConstantsForTesting::PRODUCT_G_ID_CONVERTED);
        $productChange->setVariantChanges([$variantChange]);
        $expected->addProductChange($productChange);

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
                [
                    'locationUuid' => ConstantsForTesting::LOCATION_STORE,
                    'locationType' => 'STORE',
                    'productUuid' => ConstantsForTesting::PRODUCT_G_ID_CONVERTED,
                    'variantUuid' => ConstantsForTesting::PRODUCT_G_ID_VARIANT,
                    'balance' => '3',
                ],
            ],
        ];
    }
}
