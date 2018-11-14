<?php declare(strict_types=1);
/**
 * (c) shopware AG <info@shopware.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace SwagPayPal\PayPal\Struct\Payment\Instruction;

class RecipientBanking
{
    /**
     * @var string
     */
    private $bankName;

    /**
     * @var string
     */
    private $accountHolderName;

    /**
     * @var string
     */
    private $accountNumber;

    /**
     * @var string
     */
    private $iban;

    /**
     * @var string
     */
    private $bic;

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

    public function getAccountNumber(): string
    {
        return $this->accountNumber;
    }

    public function setAccountNumber(string $accountNumber): void
    {
        $this->accountNumber = $accountNumber;
    }

    public function getIban(): string
    {
        return $this->iban;
    }

    public function setIban(string $iban): void
    {
        $this->iban = $iban;
    }

    public function getBic(): string
    {
        return $this->bic;
    }

    public function setBic(string $bic): void
    {
        $this->bic = $bic;
    }

    public static function fromArray(array $data = []): RecipientBanking
    {
        $result = new self();

        $result->setAccountHolderName($data['account_holder_name']);
        $result->setAccountNumber($data['account_number']);
        $result->setBankName($data['bank_name']);
        $result->setBic($data['bank_identifier_code']);
        $result->setIban($data['international_bank_account_number']);

        return $result;
    }
}
