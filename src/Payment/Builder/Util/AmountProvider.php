<?php declare(strict_types=1);
/*
 * (c) shopware AG <info@shopware.com>
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Swag\PayPal\Payment\Builder\Util;

use Shopware\Core\Checkout\Cart\Price\Struct\CalculatedPrice;
use Swag\PayPal\PayPal\Api\Payment\Transaction\Amount;
use Swag\PayPal\PayPal\Api\Payment\Transaction\Amount\Details;

class AmountProvider
{
    /**
     * @var PriceFormatter
     */
    private $priceFormatter;

    public function __construct()
    {
        $this->priceFormatter = new PriceFormatter();
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
