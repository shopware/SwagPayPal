<?php declare(strict_types=1);
/*
 * (c) shopware AG <info@shopware.com>
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Swag\PayPal\PaymentsApi\Builder\Util;

use Shopware\Core\Checkout\Cart\Price\Struct\CalculatedPrice;
use Swag\PayPal\RestApi\V1\Api\Payment\Transaction\Amount;
use Swag\PayPal\RestApi\V1\Api\Payment\Transaction\Amount\Details;
use Swag\PayPal\Util\PriceFormatter;

class AmountProvider
{
    private PriceFormatter $priceFormatter;

    public function __construct(PriceFormatter $priceFormatter)
    {
        $this->priceFormatter = $priceFormatter;
    }

    public function createAmount(
        CalculatedPrice $transactionAmount,
        float $shippingCostsTotal,
        string $currency
    ): Amount {
        $amount = new Amount();
        $amount->setTotal($this->priceFormatter->formatPrice($transactionAmount->getTotalPrice()));
        $amount->setCurrency($currency);
        $amount->setDetails($this->getAmountDetails($shippingCostsTotal, $transactionAmount));

        return $amount;
    }

    private function getAmountDetails(float $shippingCostsTotal, CalculatedPrice $orderTransactionAmount): Details
    {
        $amountDetails = new Details();

        $amountDetails->setShipping($this->priceFormatter->formatPrice($shippingCostsTotal));
        $totalAmount = $orderTransactionAmount->getTotalPrice();
        $amountDetails->setSubtotal($this->priceFormatter->formatPrice($totalAmount - $shippingCostsTotal));
        $amountDetails->setTax($this->priceFormatter->formatPrice(0));

        return $amountDetails;
    }
}
