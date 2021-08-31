<?php declare(strict_types=1);
/*
 * (c) shopware AG <info@shopware.com>
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Swag\PayPal\RestApi\V1\Api\Disputes\Item\Extensions;

use OpenApi\Annotations as OA;
use Swag\PayPal\RestApi\PayPalApiStruct;
use Swag\PayPal\RestApi\V1\Api\Disputes\Item\Extensions\BillingDisputeProperties\CanceledRecurringBilling;
use Swag\PayPal\RestApi\V1\Api\Disputes\Item\Extensions\BillingDisputeProperties\CreditNotProcessed;
use Swag\PayPal\RestApi\V1\Api\Disputes\Item\Extensions\BillingDisputeProperties\DuplicateTransaction;
use Swag\PayPal\RestApi\V1\Api\Disputes\Item\Extensions\BillingDisputeProperties\IncorrectTransactionAmount;
use Swag\PayPal\RestApi\V1\Api\Disputes\Item\Extensions\BillingDisputeProperties\PaymentByOtherMeans;

/**
 * @OA\Schema(schema="swag_paypal_v1_disputes_extensions_billing_dispute_properties")
 */
class BillingDisputeProperties extends PayPalApiStruct
{
    /**
     * @deprecated tag:v4.0.0 - will be strongly typed
     *
     * @var DuplicateTransaction
     * @OA\Property(ref="#/components/schemas/swag_paypal_v1_disputes_extensions_duplicate_transaction")
     */
    protected $duplicateTransaction;

    /**
     * @deprecated tag:v4.0.0 - will be strongly typed
     *
     * @var IncorrectTransactionAmount
     * @OA\Property(ref="#/components/schemas/swag_paypal_v1_disputes_extensions_incorrect_transaction_amount")
     */
    protected $incorrectTransactionAmount;

    /**
     * @deprecated tag:v4.0.0 - will be strongly typed
     *
     * @var PaymentByOtherMeans
     * @OA\Property(ref="#/components/schemas/swag_paypal_v1_disputes_extensions_payment_by_other_means")
     */
    protected $paymentByOtherMeans;

    /**
     * @deprecated tag:v4.0.0 - will be strongly typed
     *
     * @var CreditNotProcessed
     * @OA\Property(ref="#/components/schemas/swag_paypal_v1_disputes_extensions_cretid_not_processed")
     */
    protected $creditNotProcessed;

    /**
     * @deprecated tag:v4.0.0 - will be strongly typed
     *
     * @var CanceledRecurringBilling
     * @OA\Property(ref="#/components/schemas/swag_paypal_v1_disputes_extensions_canceled_recurring_billing")
     */
    protected $canceledRecurringBilling;

    public function getDuplicateTransaction(): DuplicateTransaction
    {
        return $this->duplicateTransaction;
    }

    public function setDuplicateTransaction(DuplicateTransaction $duplicateTransaction): void
    {
        $this->duplicateTransaction = $duplicateTransaction;
    }

    public function getIncorrectTransactionAmount(): IncorrectTransactionAmount
    {
        return $this->incorrectTransactionAmount;
    }

    public function setIncorrectTransactionAmount(IncorrectTransactionAmount $incorrectTransactionAmount): void
    {
        $this->incorrectTransactionAmount = $incorrectTransactionAmount;
    }

    public function getPaymentByOtherMeans(): PaymentByOtherMeans
    {
        return $this->paymentByOtherMeans;
    }

    public function setPaymentByOtherMeans(PaymentByOtherMeans $paymentByOtherMeans): void
    {
        $this->paymentByOtherMeans = $paymentByOtherMeans;
    }

    public function getCreditNotProcessed(): CreditNotProcessed
    {
        return $this->creditNotProcessed;
    }

    public function setCreditNotProcessed(CreditNotProcessed $creditNotProcessed): void
    {
        $this->creditNotProcessed = $creditNotProcessed;
    }

    public function getCanceledRecurringBilling(): CanceledRecurringBilling
    {
        return $this->canceledRecurringBilling;
    }

    public function setCanceledRecurringBilling(CanceledRecurringBilling $canceledRecurringBilling): void
    {
        $this->canceledRecurringBilling = $canceledRecurringBilling;
    }
}
