<?php declare(strict_types=1);

namespace Swag\PayPal\PayPal\Api\Payment\PaymentInstruction;

use Swag\PayPal\PayPal\Api\Common\PayPalStruct;

class RecipientBankingInstruction extends PayPalStruct
{
    /**
     * @var string
     */
    protected $bankName;

    /**
     * @var string
     */
    protected $accountHolderName;

    /**
     * @var string
     */
    protected $internationalBankAccountNumber;

    /**
     * @var string
     */
    protected $bankIdentifierCode;

    protected function setBankName(string $bankName): void
    {
        $this->bankName = $bankName;
    }

    protected function setAccountHolderName(string $accountHolderName): void
    {
        $this->accountHolderName = $accountHolderName;
    }

    protected function setInternationalBankAccountNumber(string $internationalBankAccountNumber): void
    {
        $this->internationalBankAccountNumber = $internationalBankAccountNumber;
    }

    protected function setBankIdentifierCode(string $bankIdentifierCode): void
    {
        $this->bankIdentifierCode = $bankIdentifierCode;
    }
}
