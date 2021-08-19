<?php declare(strict_types=1);
/*
 * (c) shopware AG <info@shopware.com>
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Swag\PayPal\RestApi\V1\Api\Payment\Transaction\RelatedResource;

use OpenApi\Annotations as OA;
use Swag\PayPal\RestApi\V1\Api\Payment\Transaction\RelatedResource\Capture\TransactionFee;

/**
 * @OA\Schema(schema="swag_paypal_v1_payment_transaction_capture")
 */
class Capture extends RelatedResource
{
    /**
     * @deprecated tag:v4.0.0 - will be strongly typed
     *
     * @var string
     * @OA\Property(type="string")
     */
    protected $custom;

    /**
     * @deprecated tag:v4.0.0 - will be strongly typed
     *
     * @var TransactionFee
     * @OA\Property(ref="#/components/schemas/swag_paypal_v1_common_value")
     */
    protected $transactionFee;

    /**
     * @deprecated tag:v4.0.0 - will be strongly typed
     *
     * @var string
     * @OA\Property(type="string")
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
