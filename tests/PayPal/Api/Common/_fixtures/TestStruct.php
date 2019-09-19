<?php declare(strict_types=1);

namespace Swag\PayPal\Test\PayPal\Api\Common\_fixtures;

use Swag\PayPal\PayPal\Api\Common\PayPalStruct;
use Swag\PayPal\Test\PayPal\Api\Common\_fixtures\TestStruct\Bar;
use Swag\PayPal\Test\PayPal\Api\Common\_fixtures\TestStruct\Foo;

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

    protected $notExistingClass;

    protected $notExistingCollectionClass;

    protected function setId(string $id): void
    {
        $this->id = $id;
    }

    protected function setBar(Bar $bar): void
    {
        $this->bar = $bar;
    }

    /**
     * @param Foo[] $foo
     */
    protected function setFoo(array $foo): void
    {
        $this->foo = $foo;
    }

    protected function setNotExistingClass($notExistingClass): void
    {
        $this->notExistingClass = $notExistingClass;
    }

    protected function setNotExistingCollectionClass(array $notExistingCollectionClass): void
    {
        $this->notExistingCollectionClass = $notExistingCollectionClass;
    }
}
