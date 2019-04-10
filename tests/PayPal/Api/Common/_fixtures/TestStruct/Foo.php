<?php declare(strict_types=1);

namespace Swag\PayPal\Test\PayPal\Api\Common\_fixtures\TestStruct;

use Swag\PayPal\PayPal\Api\Common\PayPalStruct;

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
