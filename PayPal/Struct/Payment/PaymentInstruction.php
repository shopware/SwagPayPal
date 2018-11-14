<?php declare(strict_types=1);
/**
 * (c) shopware AG <info@shopware.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace SwagPayPal\PayPal\Struct\Payment;

use SwagPayPal\PayPal\Struct\Payment\Instruction\Amount;
use SwagPayPal\PayPal\Struct\Payment\Instruction\PaymentInstructionType;
use SwagPayPal\PayPal\Struct\Payment\Instruction\RecipientBanking;

class PaymentInstruction
{
    /**
     * @var string
     */
    private $referenceNumber;

    /**
     * @var RecipientBanking
     */
    private $recipientBanking;

    /**
     * @var Amount
     */
    private $amount;

    /**
     * @var string
     *
     * @see PaymentInstructionType
     */
    private $type;

    /**
     * @var string
     */
    private $dueDate;

    public function getReferenceNumber(): string
    {
        return $this->referenceNumber;
    }

    public function setReferenceNumber(string $referenceNumber): void
    {
        $this->referenceNumber = $referenceNumber;
    }

    public function getRecipientBanking(): RecipientBanking
    {
        return $this->recipientBanking;
    }

    public function setRecipientBanking(RecipientBanking $recipientBanking): void
    {
        $this->recipientBanking = $recipientBanking;
    }

    public function getAmount(): Amount
    {
        return $this->amount;
    }

    public function setAmount(Amount $amount): void
    {
        $this->amount = $amount;
    }

    public function getType(): string
    {
        return $this->type;
    }

    public function setType(string $type): void
    {
        $this->type = $type;
    }

    public function getDueDate(): string
    {
        return $this->dueDate;
    }

    public function setDueDate(string $dueDate): void
    {
        $this->dueDate = $dueDate;
    }

    public static function fromArray(array $data = []): PaymentInstruction
    {
        $result = new self();

        if (array_key_exists('reference_number', $data)) {
            $result->setReferenceNumber($data['reference_number']);
        }
        if (array_key_exists('recipient_banking_instruction', $data)) {
            $result->setRecipientBanking(RecipientBanking::fromArray($data['recipient_banking_instruction']));
        }
        if (array_key_exists('amount', $data)) {
            $result->setAmount(Amount::fromArray($data['amount']));
        }
        if (array_key_exists('payment_due_date', $data)) {
            $result->setDueDate($data['payment_due_date']);
        }
        if (array_key_exists('instruction_type', $data)) {
            $result->setType($data['instruction_type']);
        }

        return $result;
    }
}
