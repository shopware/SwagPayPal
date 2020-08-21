<?php declare(strict_types=1);
/*
 * (c) shopware AG <info@shopware.com>
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Swag\PayPal\PayPal\ApiV2\Api\Order\PurchaseUnit\Payments\Capture;

use Swag\PayPal\PayPal\ApiV2\Api\Order\PurchaseUnit\Payments\Capture\SellerReceivableBreakdown\GrossAmount;
use Swag\PayPal\PayPal\ApiV2\Api\Order\PurchaseUnit\Payments\Capture\SellerReceivableBreakdown\NetAmount;
use Swag\PayPal\PayPal\ApiV2\Api\Order\PurchaseUnit\Payments\Capture\SellerReceivableBreakdown\PaypalFee;
use Swag\PayPal\PayPal\PayPalApiStruct;

class SellerReceivableBreakdown extends PayPalApiStruct
{
    /**
     * @var GrossAmount
     */
    protected $grossAmount;

    /**
     * @var PaypalFee
     */
    protected $paypalFee;

    /**
     * @var NetAmount
     */
    protected $netAmount;

    public function getGrossAmount(): GrossAmount
    {
        return $this->grossAmount;
    }

    public function setGrossAmount(GrossAmount $grossAmount): void
    {
        $this->grossAmount = $grossAmount;
    }

    public function getPaypalFee(): PaypalFee
    {
        return $this->paypalFee;
    }

    public function setPaypalFee(PaypalFee $paypalFee): void
    {
        $this->paypalFee = $paypalFee;
    }

    public function getNetAmount(): NetAmount
    {
        return $this->netAmount;
    }

    public function setNetAmount(NetAmount $netAmount): void
    {
        $this->netAmount = $netAmount;
    }
}
