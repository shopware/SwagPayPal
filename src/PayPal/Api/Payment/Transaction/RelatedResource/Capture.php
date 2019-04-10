<?php declare(strict_types=1);

namespace Swag\PayPal\PayPal\Api\Payment\Transaction\RelatedResource;

use Swag\PayPal\PayPal\Api\Payment\Transaction\RelatedResource\Capture\TransactionFee;

class Capture extends RelatedResource
{
    /**
     * @var string
     */
    private $custom;

    /**
     * @var TransactionFee
     */
    private $transactionFee;

    /**
     * @var string
     */
    private $invoiceNumber;

    protected function setCustom(string $custom): void
    {
        $this->custom = $custom;
    }

    protected function setTransactionFee(TransactionFee $transactionFee): void
    {
        $this->transactionFee = $transactionFee;
    }

    protected function setInvoiceNumber(string $invoiceNumber): void
    {
        $this->invoiceNumber = $invoiceNumber;
    }
}
