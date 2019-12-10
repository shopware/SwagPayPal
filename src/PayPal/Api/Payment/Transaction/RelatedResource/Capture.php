<?php declare(strict_types=1);
/*
 * (c) shopware AG <info@shopware.com>
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

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
