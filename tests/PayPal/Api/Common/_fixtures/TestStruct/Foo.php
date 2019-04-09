<?php declare(strict_types=1);

namespace SwagPayPal\Test\PayPal\Api\Common\_fixtures\TestStruct;

use SwagPayPal\PayPal\Api\Common\PayPalStruct;

class Foo extends PayPalStruct
{
    /**
     * @var string
     */
    protected $fooBaz;

    protected function setFooBaz(string $fooBaz): void
    {
        $this->fooBaz = $fooBaz;
    }
}
