<?php declare(strict_types=1);

namespace Swag\PayPal\PayPal\Api\Payment\Transaction;

use Swag\PayPal\PayPal\Api\Common\PayPalStruct;

class Payee extends PayPalStruct
{
    /**
     * @var string
     */
    private $merchantId;

    /**
     * @var string
     */
    private $email;

    protected function setMerchantId(string $merchantId): void
    {
        $this->merchantId = $merchantId;
    }

    protected function setEmail(string $email): void
    {
        $this->email = $email;
    }
}
