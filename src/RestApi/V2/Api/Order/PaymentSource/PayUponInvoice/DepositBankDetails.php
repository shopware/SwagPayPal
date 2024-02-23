<?php declare(strict_types=1);
/*
 * (c) shopware AG <info@shopware.com>
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Swag\PayPal\RestApi\V2\Api\Order\PaymentSource\PayUponInvoice;

use OpenApi\Attributes as OA;
use Shopware\Core\Framework\Log\Package;
use Swag\PayPal\RestApi\PayPalApiStruct;

#[OA\Schema(schema: 'swag_paypal_v2_order_payment_source_pay_upon_invoice_deposit_bank_details')]
#[Package('checkout')]
class DepositBankDetails extends PayPalApiStruct
{
    #[OA\Property(type: 'string')]
    protected string $bic;

    #[OA\Property(type: 'string')]
    protected string $bankName;

    #[OA\Property(type: 'string')]
    protected string $iban;

    #[OA\Property(type: 'string')]
    protected string $accountHolderName;

    public function getBic(): string
    {
        return $this->bic;
    }

    public function setBic(string $bic): void
    {
        $this->bic = $bic;
    }

    public function getBankName(): string
    {
        return $this->bankName;
    }

    public function setBankName(string $bankName): void
    {
        $this->bankName = $bankName;
    }

    public function getIban(): string
    {
        return $this->iban;
    }

    public function setIban(string $iban): void
    {
        $this->iban = $iban;
    }

    public function getAccountHolderName(): string
    {
        return $this->accountHolderName;
    }

    public function setAccountHolderName(string $accountHolderName): void
    {
        $this->accountHolderName = $accountHolderName;
    }
}
