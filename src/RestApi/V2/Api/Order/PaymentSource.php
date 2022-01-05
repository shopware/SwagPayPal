<?php declare(strict_types=1);
/*
 * (c) shopware AG <info@shopware.com>
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Swag\PayPal\RestApi\V2\Api\Order;

use OpenApi\Annotations as OA;
use Swag\PayPal\RestApi\PayPalApiStruct;
use Swag\PayPal\RestApi\V2\Api\Order\PaymentSource\PayUponInvoice;

/**
 * @OA\Schema(schema="swag_paypal_v2_order_payment_source")
 */
class PaymentSource extends PayPalApiStruct
{
    /**
     * @OA\Property(ref="#/components/schemas/swag_paypal_v2_order_pay_upon_invoice")
     */
    protected ?PayUponInvoice $payUponInvoice;

    public function getPayUponInvoice(): ?PayUponInvoice
    {
        return $this->payUponInvoice;
    }

    public function setPayUponInvoice(?PayUponInvoice $payUponInvoice): void
    {
        $this->payUponInvoice = $payUponInvoice;
    }
}
