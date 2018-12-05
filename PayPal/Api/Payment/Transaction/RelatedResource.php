<?php declare(strict_types=1);
/**
 * (c) shopware AG <info@shopware.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace SwagPayPal\PayPal\Api\Payment\Transaction;

use SwagPayPal\PayPal\Api\Payment\Transaction\RelatedResource\Authorization;
use SwagPayPal\PayPal\Api\Payment\Transaction\RelatedResource\Order;
use SwagPayPal\PayPal\Api\Payment\Transaction\RelatedResource\Sale;
use SwagPayPal\PayPal\Api\PayPalStruct;

class RelatedResource extends PayPalStruct
{
    /**
     * @var Sale|null
     */
    private $sale;

    /**
     * @var Authorization|null
     */
    private $authorization;

    /**
     * @var Order|null
     */
    private $order;

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
