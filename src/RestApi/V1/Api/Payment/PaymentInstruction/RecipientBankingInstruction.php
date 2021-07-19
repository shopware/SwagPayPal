<?php declare(strict_types=1);
/*
 * (c) shopware AG <info@shopware.com>
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Swag\PayPal\RestApi\V1\Api\Payment\PaymentInstruction;

use Swag\PayPal\RestApi\PayPalApiStruct;

class RecipientBankingInstruction extends PayPalApiStruct
{
    /**
     * @deprecated tag:v4.0.0 - will be strongly typed
     *
     * @var string
     */
    protected $bankName;

    /**
     * @deprecated tag:v4.0.0 - will be strongly typed
     *
     * @var string
     */
    protected $accountHolderName;

    /**
     * @deprecated tag:v4.0.0 - will be strongly typed
     *
     * @var string
     */
    protected $internationalBankAccountNumber;

    /**
     * @deprecated tag:v4.0.0 - will be strongly typed
     *
     * @var string
     */
    protected $bankIdentifierCode;

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
