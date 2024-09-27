<?php declare(strict_types=1);
/*
 * (c) shopware AG <info@shopware.com>
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Swag\PayPal\PaymentsApi\Builder\Util;

use Shopware\Core\Checkout\Cart\Price\Struct\CalculatedPrice;
use Shopware\Core\Framework\Log\Package;
use Swag\PayPal\RestApi\V1\Api\Common\Amount;
use Swag\PayPal\RestApi\V1\Api\Common\Details;
use Swag\PayPal\Util\PriceFormatter;

#[Package('checkout')]
class AmountProvider
{
    private PriceFormatter $priceFormatter;

    /**
     * @internal
     */
    public function __construct(PriceFormatter $priceFormatter)
    {
        $this->priceFormatter = $priceFormatter;
    }

    public function createAmount(
        CalculatedPrice $transactionAmount,
        float $shippingCostsTotal,
        string $currencyCode,
    ): Amount {
        $amount = new Amount();
        $amount->setTotal($this->priceFormatter->formatPrice($transactionAmount->getTotalPrice(), $currencyCode));
        $amount->setCurrency($currencyCode);
        $amount->setDetails($this->getAmountDetails($shippingCostsTotal, $transactionAmount, $currencyCode));

        return $amount;
    }

    private function getAmountDetails(float $shippingCostsTotal, CalculatedPrice $orderTransactionAmount, string $currencyCode): Details
    {
        $amountDetails = new Details();

        $amountDetails->setShipping($this->priceFormatter->formatPrice($shippingCostsTotal, $currencyCode));
        $totalAmount = $orderTransactionAmount->getTotalPrice();
        $amountDetails->setSubtotal($this->priceFormatter->formatPrice($totalAmount - $shippingCostsTotal, $currencyCode));
        $amountDetails->setTax($this->priceFormatter->formatPrice(0, $currencyCode));

        return $amountDetails;
    }
}
