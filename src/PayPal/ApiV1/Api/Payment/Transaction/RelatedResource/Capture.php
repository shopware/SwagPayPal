<?php declare(strict_types=1);
/*
 * (c) shopware AG <info@shopware.com>
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Swag\PayPal\PayPal\ApiV1\Api\Payment\Transaction\RelatedResource;

use Swag\PayPal\PayPal\ApiV1\Api\Payment\Transaction\RelatedResource\Capture\TransactionFee;

class Capture extends RelatedResource
{
    /**
     * @var string
     */
    protected $custom;

    /**
     * @var TransactionFee
     */
    protected $transactionFee;

    /**
     * @var string
     */
    protected $invoiceNumber;

    public function getCustom(): string
    {
        return $this->custom;
    }

    public function setCustom(string $custom): void
    {
        $this->custom = $custom;
    }

    public function getTransactionFee(): TransactionFee
    {
        return $this->transactionFee;
    }

    public function setTransactionFee(TransactionFee $transactionFee): void
    {
        $this->transactionFee = $transactionFee;
    }

    public function getInvoiceNumber(): string
    {
        return $this->invoiceNumber;
    }

    public function setInvoiceNumber(string $invoiceNumber): void
    {
        $this->invoiceNumber = $invoiceNumber;
    }
}
