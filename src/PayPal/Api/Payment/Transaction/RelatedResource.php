<?php declare(strict_types=1);
/*
 * (c) shopware AG <info@shopware.com>
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Swag\PayPal\PayPal\Api\Payment\Transaction;

use Swag\PayPal\PayPal\Api\Common\PayPalStruct;
use Swag\PayPal\PayPal\Api\Payment\Transaction\RelatedResource\Authorization;
use Swag\PayPal\PayPal\Api\Payment\Transaction\RelatedResource\Capture;
use Swag\PayPal\PayPal\Api\Payment\Transaction\RelatedResource\Order;
use Swag\PayPal\PayPal\Api\Payment\Transaction\RelatedResource\Refund;
use Swag\PayPal\PayPal\Api\Payment\Transaction\RelatedResource\Sale;
use Swag\PayPal\PayPal\PaymentIntent;

class RelatedResource extends PayPalStruct
{
    public const SALE = PaymentIntent::SALE;
    public const AUTHORIZE = PaymentIntent::AUTHORIZE;
    public const ORDER = PaymentIntent::ORDER;
    public const REFUND = 'refund';
    public const CAPTURE = 'capture';

    /**
     * @var Sale|null
     */
    protected $sale;

    /**
     * @var Authorization|null
     */
    protected $authorization;

    /**
     * @var Order|null
     */
    protected $order;

    /**
     * @var Refund|null
     */
    protected $refund;

    /**
     * @var Capture|null
     */
    protected $capture;

    public function getSale(): ?Sale
    {
        return $this->sale;
    }

    public function getAuthorization(): ?Authorization
    {
        return $this->authorization;
    }

    public function getOrder(): ?Order
    {
        return $this->order;
    }

    public function getRefund(): ?Refund
    {
        return $this->refund;
    }

    public function setRefund(Refund $refund): void
    {
        $this->refund = $refund;
    }

    public function getCapture(): ?Capture
    {
        return $this->capture;
    }

    public function setCapture(Capture $capture): void
    {
        $this->capture = $capture;
    }

    protected function setSale(Sale $sale): void
    {
        $this->sale = $sale;
    }

    protected function setAuthorization(Authorization $authorization): void
    {
        $this->authorization = $authorization;
    }

    protected function setOrder(Order $order): void
    {
        $this->order = $order;
    }
}
