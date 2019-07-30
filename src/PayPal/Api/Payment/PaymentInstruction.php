<?php declare(strict_types=1);

namespace Swag\PayPal\PayPal\Api\Payment;

use Swag\PayPal\PayPal\Api\Common\PayPalStruct;
use Swag\PayPal\PayPal\Api\Payment\PaymentInstruction\Amount;
use Swag\PayPal\PayPal\Api\Payment\PaymentInstruction\Link;
use Swag\PayPal\PayPal\Api\Payment\PaymentInstruction\RecipientBankingInstruction;

class PaymentInstruction extends PayPalStruct
{
    public const TYPE_INVOICE = 'PAY_UPON_INVOICE';

    /**
     * @var string
     */
    protected $referenceNumber;

    /**
     * @var RecipientBankingInstruction
     */
    protected $recipientBankingInstruction;

    /**
     * @var Amount
     */
    protected $amount;

    /**
     * @var string
     */
    protected $paymentDueDate;

    /**
     * @var string
     */
    private $instructionType;

    /**
     * @var Link[]
     */
    private $links;

    public function getInstructionType(): string
    {
        return $this->instructionType;
    }

    protected function setReferenceNumber(string $referenceNumber): void
    {
        $this->referenceNumber = $referenceNumber;
    }

    protected function setInstructionType(string $instructionType): void
    {
        $this->instructionType = $instructionType;
    }

    protected function setRecipientBankingInstruction(RecipientBankingInstruction $recipientBankingInstruction): void
    {
        $this->recipientBankingInstruction = $recipientBankingInstruction;
    }

    protected function setAmount(Amount $amount): void
    {
        $this->amount = $amount;
    }

    protected function setPaymentDueDate(string $paymentDueDate): void
    {
        $this->paymentDueDate = $paymentDueDate;
    }

    /**
     * @param Link[] $links
     */
    protected function setLinks(array $links): void
    {
        $this->links = $links;
    }
}
