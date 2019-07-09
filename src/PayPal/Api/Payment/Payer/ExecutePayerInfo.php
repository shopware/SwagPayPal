<?php declare(strict_types=1);

namespace Swag\PayPal\PayPal\Api\Payment\Payer;

use Swag\PayPal\PayPal\Api\Common\PayPalStruct;

class ExecutePayerInfo extends PayPalStruct
{
    /**
     * @var string
     */
    protected $payerId;

    public function setPayerId(string $payerId): void
    {
        $this->payerId = $payerId;
    }

    public function getPayerId(): string
    {
        return $this->payerId;
    }
}
