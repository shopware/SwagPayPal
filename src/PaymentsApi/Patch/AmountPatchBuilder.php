<?php declare(strict_types=1);
/*
 * (c) shopware AG <info@shopware.com>
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Swag\PayPal\PaymentsApi\Patch;

use Shopware\Core\Checkout\Cart\Price\Struct\CalculatedPrice;
use Swag\PayPal\PaymentsApi\Builder\Util\AmountProvider;
use Swag\PayPal\PayPal\ApiV1\Api\Patch;

class AmountPatchBuilder
{
    public function createAmountPatch(
        CalculatedPrice $orderTransactionAmount,
        float $shippingCosts,
        string $currency
    ): Patch {
        $amount = (new AmountProvider())->createAmount($orderTransactionAmount, $shippingCosts, $currency);
        $amountArray = \json_decode((string) \json_encode($amount), true);

        $amountPatch = new Patch();
        $amountPatch->assign([
            'op' => Patch::OPERATION_REPLACE,
            'path' => '/transactions/0/amount',
        ]);
        $amountPatch->setValue($amountArray);

        return $amountPatch;
    }
}
