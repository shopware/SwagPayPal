<?php declare(strict_types=1);
/*
 * (c) shopware AG <info@shopware.com>
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Swag\PayPal\RestApi\V1\Api\Disputes\Item\Extensions;

use Swag\PayPal\RestApi\PayPalApiStruct;
use Swag\PayPal\RestApi\V1\Api\Disputes\Item\Extensions\BillingDisputeProperties\CanceledRecurringBilling;
use Swag\PayPal\RestApi\V1\Api\Disputes\Item\Extensions\BillingDisputeProperties\CreditNotProcessed;
use Swag\PayPal\RestApi\V1\Api\Disputes\Item\Extensions\BillingDisputeProperties\DuplicateTransaction;
use Swag\PayPal\RestApi\V1\Api\Disputes\Item\Extensions\BillingDisputeProperties\IncorrectTransactionAmount;
use Swag\PayPal\RestApi\V1\Api\Disputes\Item\Extensions\BillingDisputeProperties\PaymentByOtherMeans;

class BillingDisputeProperties extends PayPalApiStruct
{
    /**
     * @deprecated tag:v4.0.0 - will be strongly typed
     *
     * @var DuplicateTransaction
     */
    protected $duplicateTransaction;

    /**
     * @deprecated tag:v4.0.0 - will be strongly typed
     *
     * @var IncorrectTransactionAmount
     */
    protected $incorrectTransactionAmount;

    /**
     * @deprecated tag:v4.0.0 - will be strongly typed
     *
     * @var PaymentByOtherMeans
     */
    protected $paymentByOtherMeans;

    /**
     * @deprecated tag:v4.0.0 - will be strongly typed
     *
     * @var CreditNotProcessed
     */
    protected $creditNotProcessed;

    /**
     * @deprecated tag:v4.0.0 - will be strongly typed
     *
     * @var CanceledRecurringBilling
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
