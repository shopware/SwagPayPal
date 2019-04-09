<?php declare(strict_types=1);

namespace SwagPayPal\Test\PayPal\Api\Common\_fixtures;

use SwagPayPal\PayPal\Api\Common\PayPalStruct;
use SwagPayPal\Test\PayPal\Api\Common\_fixtures\TestStruct\Bar;
use SwagPayPal\Test\PayPal\Api\Common\_fixtures\TestStruct\Foo;

class TestStruct extends PayPalStruct
{
    /**
     * @var string
     */
    protected $id;

    /**
     * @var Bar
     */
    protected $bar;

    /**
     * @var Foo[]
     */
    protected $foo;

    public function setBar(Bar $bar): void
    {
        $this->bar = $bar;
    }

    /**
     * @param Foo[] $foo
     */
    public function setFoo(array $foo): void
    {
        $this->foo = $foo;
    }

    protected function setId(string $id): void
    {
        $this->id = $id;
    }
}
