<?php declare(strict_types=1);
/*
 * (c) shopware AG <info@shopware.com>
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Swag\PayPal\OrdersApi\Patch;

use Shopware\Core\Checkout\Cart\Price\Struct\CalculatedPrice;
use Shopware\Core\System\Currency\CurrencyEntity;
use Swag\PayPal\OrdersApi\Builder\Util\AmountProvider;
use Swag\PayPal\RestApi\V2\Api\Order\PurchaseUnit;
use Swag\PayPal\RestApi\V2\Api\Patch;

/**
 * @deprecated tag:v4.0.0 - will be removed, is part of Swag\PayPal\OrdersApi\Patch\PurchaseUnitPatchBuilder now
 */
class AmountPatchBuilder
{
    /**
     * @var AmountProvider
     */
    private $amountProvider;

    public function __construct(AmountProvider $amountProvider)
    {
        $this->amountProvider = $amountProvider;
    }

    public function createAmountPatch(
        CalculatedPrice $orderTransactionAmount,
        CalculatedPrice $shippingCosts,
        CurrencyEntity $currency,
        PurchaseUnit $purchaseUnit,
        bool $isNet
    ): Patch {
        $amount = $this->amountProvider->createAmount(
            $orderTransactionAmount,
            $shippingCosts,
            $currency,
            $purchaseUnit,
            $isNet
        );
        $amountArray = \json_decode((string) \json_encode($amount), true);

        $amountPatch = new Patch();
        $amountPatch->assign([
            'op' => Patch::OPERATION_REPLACE,
            'path' => "/purchase_units/@reference_id=='default'/amount",
        ]);
        $amountPatch->setValue($amountArray);

        return $amountPatch;
    }
}
