<?php

declare(strict_types=1);
/*
 * (c) shopware AG <info@shopware.com>
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Swag\PayPal\RestApi\V1\Api\Payment;

use OpenApi\Annotations as OA;
use Shopware\Core\Framework\Log\Package;
use Swag\PayPal\RestApi\PayPalApiStruct;
use Swag\PayPal\RestApi\V1\Api\Common\LinkCollection;
use Swag\PayPal\RestApi\V1\Api\Common\Value;
use Swag\PayPal\RestApi\V1\Api\Payment\PaymentInstruction\RecipientBankingInstruction;

/**
 * @OA\Schema(schema="swag_paypal_v1_payment_payment_instruction")
 */
#[Package('checkout')]
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
    protected Value $amount;

    /**
     * @OA\Property(type="string")
     */
    protected string $paymentDueDate;

    /**
     * @OA\Property(type="string")
     */
    protected string $instructionType;

    /**
     * @OA\Property(type="array", items={"$ref": "#/components/schemas/swag_paypal_v1_common_link"})
     */
    protected LinkCollection $links;

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

    public function getAmount(): Value
    {
        return $this->amount;
    }

    public function setAmount(Value $amount): void
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

    public function getLinks(): LinkCollection
    {
        return $this->links;
    }

    public function setLinks(LinkCollection $links): void
    {
        $this->links = $links;
    }
}
