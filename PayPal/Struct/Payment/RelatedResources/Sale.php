<?php declare(strict_types=1);
/**
 * (c) shopware AG <info@shopware.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace SwagPayPal\PayPal\Struct\Payment\RelatedResources;

class Sale extends RelatedResource
{
    /**
     * @var string
     */
    private $paymentMode;

    /**
     * @var string
     */
    private $protectionEligibility;

    /**
     * @var string
     */
    private $protectionEligibilityType;

    /**
     * @var TransactionFee
     */
    private $transactionFee;

    /**
     * @var string
     */
    private $receiptId;

    public function getPaymentMode(): string
    {
        return $this->paymentMode;
    }

    public function setPaymentMode(string $paymentMode): void
    {
        $this->paymentMode = $paymentMode;
    }

    public function getProtectionEligibility(): string
    {
        return $this->protectionEligibility;
    }

    public function setProtectionEligibility(string $protectionEligibility): void
    {
        $this->protectionEligibility = $protectionEligibility;
    }

    public function getProtectionEligibilityType(): string
    {
        return $this->protectionEligibilityType;
    }

    public function setProtectionEligibilityType(string $protectionEligibilityType): void
    {
        $this->protectionEligibilityType = $protectionEligibilityType;
    }

    public function getTransactionFee(): TransactionFee
    {
        return $this->transactionFee;
    }

    public function setTransactionFee(TransactionFee $transactionFee): void
    {
        $this->transactionFee = $transactionFee;
    }

    public function getReceiptId(): string
    {
        return $this->receiptId;
    }

    public function setReceiptId(string $receiptId): void
    {
        $this->receiptId = $receiptId;
    }

    public static function fromArray(array $data): Sale
    {
        $result = new self();
        $result->prepare($result, $data, ResourceType::SALE);

        if (array_key_exists('payment_mode', $data)) {
            $result->setPaymentMode($data['payment_mode']);
        }
        if (array_key_exists('protection_eligibility', $data)) {
            $result->setProtectionEligibility($data['protection_eligibility']);
        }
        if (array_key_exists('protection_eligibility_type', $data)) {
            $result->setProtectionEligibilityType($data['protection_eligibility_type']);
        }
        if (array_key_exists('receipt_id', $data)) {
            $result->setReceiptId($data['receipt_id']);
        }

        if (array_key_exists('transaction_fee', $data) && \is_array($data['transaction_fee'])) {
            $result->setTransactionFee(TransactionFee::fromArray($data['transaction_fee']));
        }

        return $result;
    }
}
