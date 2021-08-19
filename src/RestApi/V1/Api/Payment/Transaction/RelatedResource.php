<?php declare(strict_types=1);
/*
 * (c) shopware AG <info@shopware.com>
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Swag\PayPal\RestApi\V1\Api\Payment\Transaction;

use OpenApi\Annotations as OA;
use Swag\PayPal\RestApi\PayPalApiStruct;
use Swag\PayPal\RestApi\V1\Api\Payment\Transaction\RelatedResource\Authorization;
use Swag\PayPal\RestApi\V1\Api\Payment\Transaction\RelatedResource\Capture;
use Swag\PayPal\RestApi\V1\Api\Payment\Transaction\RelatedResource\Order;
use Swag\PayPal\RestApi\V1\Api\Payment\Transaction\RelatedResource\Refund;
use Swag\PayPal\RestApi\V1\Api\Payment\Transaction\RelatedResource\Sale;
use Swag\PayPal\RestApi\V1\PaymentIntentV1;

/**
 * @OA\Schema(schema="swag_paypal_v1_payment_transaction_related_resource")
 */
class RelatedResource extends PayPalApiStruct
{
    public const SALE = PaymentIntentV1::SALE;
    public const AUTHORIZE = PaymentIntentV1::AUTHORIZE;
    public const ORDER = PaymentIntentV1::ORDER;
    public const REFUND = 'refund';
    public const CAPTURE = 'capture';

    /**
     * @deprecated tag:v4.0.0 - will be strongly typed
     *
     * @var Sale|null
     * @OA\Property(ref="#/components/schemas/swag_paypal_v1_payment_transaction_sale", nullable=true)
     */
    protected $sale;

    /**
     * @deprecated tag:v4.0.0 - will be strongly typed
     *
     * @var Authorization|null
     * @OA\Property(ref="#/components/schemas/swag_paypal_v1_payment_transaction_authorization", nullable=true)
     */
    protected $authorization;

    /**
     * @deprecated tag:v4.0.0 - will be strongly typed
     *
     * @var Order|null
     * @OA\Property(ref="#/components/schemas/swag_paypal_v1_payment_transaction_order", nullable=true)
     */
    protected $order;

    /**
     * @deprecated tag:v4.0.0 - will be strongly typed
     *
     * @var Refund|null
     * @OA\Property(ref="#/components/schemas/swag_paypal_v1_payment_transaction_refund", nullable=true)
     */
    protected $refund;

    /**
     * @deprecated tag:v4.0.0 - will be strongly typed
     *
     * @var Capture|null
     * @OA\Property(ref="#/components/schemas/swag_paypal_v1_payment_transaction_capture", nullable=true)
     */
    protected $capture;

    public function getSale(): ?Sale
    {
        return $this->sale;
    }

    public function setSale(?Sale $sale): void
    {
        $this->sale = $sale;
    }

    public function getAuthorization(): ?Authorization
    {
        return $this->authorization;
    }

    public function setAuthorization(?Authorization $authorization): void
    {
        $this->authorization = $authorization;
    }

    public function getOrder(): ?Order
    {
        return $this->order;
    }

    public function setOrder(?Order $order): void
    {
        $this->order = $order;
    }

    public function getRefund(): ?Refund
    {
        return $this->refund;
    }

    public function setRefund(?Refund $refund): void
    {
        $this->refund = $refund;
    }

    public function getCapture(): ?Capture
    {
        return $this->capture;
    }

    public function setCapture(?Capture $capture): void
    {
        $this->capture = $capture;
    }
}
