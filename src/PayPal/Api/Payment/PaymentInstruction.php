<?php declare(strict_types=1);
/*
 * (c) shopware AG <info@shopware.com>
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

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
    protected $instructionType;

    /**
     * @var Link[]
     */
    protected $links;

    public function getReferenceNumber(): string
    {
        return $this->referenceNumber;
    }

    public function setReferenceNumber(string $referenceNumber): void
    {
        $this->referenceNumber = $referenceNumber;
    }

    public function getRecipientBankingInstruction(): RecipientBankingInstruction
    {
        return $this->recipientBankingInstruction;
    }

    public function setRecipientBankingInstruction(RecipientBankingInstruction $recipientBankingInstruction): void
    {
        $this->recipientBankingInstruction = $recipientBankingInstruction;
    }

    public function getAmount(): Amount
    {
        return $this->amount;
    }

    public function setAmount(Amount $amount): void
    {
        $this->amount = $amount;
    }

    public function getPaymentDueDate(): string
    {
        return $this->paymentDueDate;
    }

    public function setPaymentDueDate(string $paymentDueDate): void
    {
        $this->paymentDueDate = $paymentDueDate;
    }

    public function getInstructionType(): string
    {
        return $this->instructionType;
    }

    public function setInstructionType(string $instructionType): void
    {
        $this->instructionType = $instructionType;
    }

    /**
     * @return Link[]
     */
    public function getLinks(): array
    {
        return $this->links;
    }

    /**
     * @param Link[] $links
     */
    public function setLinks(array $links): void
    {
        $this->links = $links;
    }
}
