<?php declare(strict_types=1);
/*
 * (c) shopware AG <info@shopware.com>
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Swag\PayPal\RestApi\V1\Api\Payment\PaymentInstruction;

use OpenApi\Attributes as OA;
use Shopware\Core\Framework\Log\Package;
use Swag\PayPal\RestApi\PayPalApiStruct;

#[OA\Schema(schema: 'swag_paypal_v1_payment_payment_instruction_recipient_banking_instruction')]
#[Package('checkout')]
class RecipientBankingInstruction extends PayPalApiStruct
{
    #[OA\Property(type: 'string')]
    protected string $bankName;

    #[OA\Property(type: 'string')]
    protected string $accountHolderName;

    #[OA\Property(type: 'string')]
    protected string $internationalBankAccountNumber;

    #[OA\Property(type: 'string')]
    protected string $bankIdentifierCode;

    public function getBankName(): string
    {
        return $this->bankName;
    }

    public function setBankName(string $bankName): void
    {
        $this->bankName = $bankName;
    }

    public function getAccountHolderName(): string
    {
        return $this->accountHolderName;
    }

    public function setAccountHolderName(string $accountHolderName): void
    {
        $this->accountHolderName = $accountHolderName;
    }

    public function getInternationalBankAccountNumber(): string
    {
        return $this->internationalBankAccountNumber;
    }

    public function setInternationalBankAccountNumber(string $internationalBankAccountNumber): void
    {
        $this->internationalBankAccountNumber = $internationalBankAccountNumber;
    }

    public function getBankIdentifierCode(): string
    {
        return $this->bankIdentifierCode;
    }

    public function setBankIdentifierCode(string $bankIdentifierCode): void
    {
        $this->bankIdentifierCode = $bankIdentifierCode;
    }
}
