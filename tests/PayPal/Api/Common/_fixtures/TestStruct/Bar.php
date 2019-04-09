<?php declare(strict_types=1);

namespace Swag\PayPal\Test\PayPal\Api\Common\_fixtures\TestStruct;

use Swag\PayPal\PayPal\Api\Common\PayPalStruct;

class Bar extends PayPalStruct
{
    /**
     * @var string
     */
    protected $bar;

    protected function setBar(string $bar): void
    {
        $this->bar = $bar;
    }
}
