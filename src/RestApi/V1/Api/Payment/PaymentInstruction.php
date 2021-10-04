<?php declare(strict_types=1);
/*
 * (c) shopware AG <info@shopware.com>
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Swag\PayPal\RestApi\V1\Api\Payment;

use OpenApi\Annotations as OA;
use Swag\PayPal\RestApi\PayPalApiStruct;
use Swag\PayPal\RestApi\V1\Api\Payment\PaymentInstruction\Amount;
use Swag\PayPal\RestApi\V1\Api\Payment\PaymentInstruction\Link;
use Swag\PayPal\RestApi\V1\Api\Payment\PaymentInstruction\RecipientBankingInstruction;

/**
 * @OA\Schema(schema="swag_paypal_v1_payment_payment_instruction")
 */
class PaymentInstruction extends PayPalApiStruct
{
    public const TYPE_INVOICE = 'PAY_UPON_INVOICE';

    /**
     * @OA\Property(type="string")
     */
    protected string $referenceNumber;

    /**
     * @OA\Property(ref="#/components/schemas/swag_paypal_v1_payment_recipient_banking_instruction")
     */
    protected RecipientBankingInstruction $recipientBankingInstruction;

    /**
     * @OA\Property(ref="#/components/schemas/swag_paypal_v1_common_value")
     */
    protected Amount $amount;

    /**
     * @OA\Property(type="string")
     */
    protected string $paymentDueDate;

    /**
     * @OA\Property(type="string")
     */
    protected string $instructionType;

    /**
     * @var Link[]
     * @OA\Property(type="array", items={"$ref": "#/components/schemas/swag_paypal_v1_common_link"})
     */
    protected array $links;

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
