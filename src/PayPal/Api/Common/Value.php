<?php declare(strict_types=1);

namespace Swag\PayPal\PayPal\Api\Common;

abstract class Value extends PayPalStruct
{
    /**
     * @var string
     */
    protected $currency;

    /**
     * @var string
     */
    protected $value;

    protected function setCurrency(string $currency): void
    {
        $this->currency = $currency;
    }

    protected function setValue(string $value): void
    {
        $this->value = $value;
    }
}
