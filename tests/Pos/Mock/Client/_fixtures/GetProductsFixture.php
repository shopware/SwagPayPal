<?php declare(strict_types=1);
/*
 * (c) shopware AG <info@shopware.com>
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Swag\PayPal\Test\Pos\Mock\Client\_fixtures;

use Shopware\Core\Framework\Log\Package;
use Swag\PayPal\Test\Pos\ConstantsForTesting;

/**
 * @internal
 */
#[Package('checkout')]
class GetProductsFixture
{
    public static function get(): array
    {
        // omitting many values here, only UUIDs needed for Deletion of Unsynced products
        return [
            ['uuid' => ConstantsForTesting::PRODUCT_A_ID_CONVERTED],
            ['uuid' => ConstantsForTesting::PRODUCT_D_ID_CONVERTED],
            ['uuid' => ConstantsForTesting::PRODUCT_E_ID_CONVERTED],
            ['uuid' => ConstantsForTesting::PRODUCT_F_ID_CONVERTED],
            ['uuid' => ConstantsForTesting::PRODUCT_G_ID_CONVERTED],
        ];
    }
}
