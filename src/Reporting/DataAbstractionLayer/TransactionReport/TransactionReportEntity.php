<?php declare(strict_types=1);
/*
 * (c) shopware AG <info@shopware.com>
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Swag\PayPal\Reporting\DataAbstractionLayer\TransactionReport;

use Shopware\Core\Checkout\Order\Aggregate\OrderTransaction\OrderTransactionEntity;
use Shopware\Core\Framework\DataAbstractionLayer\Entity;
use Shopware\Core\Framework\Log\Package;

/**
 * @internal
 *
 * @codeCoverageIgnore
 */
#[Package('checkout')]
class TransactionReportEntity extends Entity
{
    protected string $orderTransactionId;

    protected string $orderTransactionVersionId;

    protected string $currencyIso;

    protected float $totalPrice;

    protected ?OrderTransactionEntity $orderTransaction = null;

    public function getOrderTransactionId(): string
    {
        return $this->orderTransactionId;
    }

    public function setOrderTransactionId(string $orderTransactionId): void
    {
        $this->orderTransactionId = $orderTransactionId;
    }

    public function getOrderTransactionVersionId(): string
    {
        return $this->orderTransactionVersionId;
    }

    public function setOrderTransactionVersionId(string $orderTransactionVersionId): void
    {
        $this->orderTransactionVersionId = $orderTransactionVersionId;
    }

    public function getCurrencyIso(): string
    {
        return $this->currencyIso;
    }

    public function setCurrencyIso(string $currencyIso): void
    {
        $this->currencyIso = $currencyIso;
    }

    public function getTotalPrice(): float
    {
        return $this->totalPrice;
    }

    public function setTotalPrice(float $totalPrice): void
    {
        $this->totalPrice = $totalPrice;
    }

    public function getOrderTransaction(): ?OrderTransactionEntity
    {
        return $this->orderTransaction;
    }

    public function setOrderTransaction(?OrderTransactionEntity $orderTransaction): void
    {
        $this->orderTransaction = $orderTransaction;
    }
}
