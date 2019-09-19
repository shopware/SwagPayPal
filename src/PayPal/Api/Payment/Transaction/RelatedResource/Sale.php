<?php declare(strict_types=1);

namespace Swag\PayPal\PayPal\Api\Payment\Transaction\RelatedResource;

use Swag\PayPal\PayPal\Api\Payment\Transaction\RelatedResource\Sale\TransactionFee;

class Sale extends RelatedResource
{
    /**
     * @var TransactionFee
     */
    protected $transactionFee;

    protected function setTransactionFee(TransactionFee $transactionFee): void
    {
        $this->transactionFee = $transactionFee;
    }
}
