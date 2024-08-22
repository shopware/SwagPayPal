<?php declare(strict_types=1);
/*
 * (c) shopware AG <info@shopware.com>
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Swag\PayPal\Test\RestApi\_fixtures;

use Shopware\Core\Framework\Log\Package;
use Swag\PayPal\RestApi\PayPalApiStruct;
use Swag\PayPal\Test\RestApi\_fixtures\TestStruct\Bar;
use Swag\PayPal\Test\RestApi\_fixtures\TestStruct\Foo;

/**
 * @internal
 */
#[Package('checkout')]
class TestStruct extends PayPalApiStruct
{
    protected string $id;

    protected Bar $bar;

    /**
     * @var Foo[]
     */
    protected array $foo;

    protected object $notExistingClass;

    protected array $notExistingCollectionClass;

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

    /**
     * @param object $notExistingClass
     */
    protected function setNotExistingClass($notExistingClass): void
    {
        $this->notExistingClass = $notExistingClass;
    }

    protected function setNotExistingCollectionClass(array $notExistingCollectionClass): void
    {
        $this->notExistingCollectionClass = $notExistingCollectionClass;
    }
}
