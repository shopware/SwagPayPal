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
use Swag\PayPal\Test\RestApi\_fixtures\TestStruct\FooCollection;

/**
 * @internal
 */
#[Package('checkout')]
class TestStruct extends PayPalApiStruct
{
    protected string $id;

    protected Bar $bar;

    protected FooCollection $foo;

    protected object $notExistingClass;

    protected array $notExistingCollectionClass;

    /**
     * @var string[]
     */
    protected array $scalarArray;

    protected function setId(string $id): void
    {
        $this->id = $id;
    }

    protected function setBar(Bar $bar): void
    {
        $this->bar = $bar;
    }

    protected function setFoo(FooCollection $foo): void
    {
        $this->foo = $foo;
    }

    protected function setNotExistingClass(object $notExistingClass): void
    {
        $this->notExistingClass = $notExistingClass;
    }

    protected function setNotExistingCollectionClass(array $notExistingCollectionClass): void
    {
        $this->notExistingCollectionClass = $notExistingCollectionClass;
    }

    /**
     * @param string[] $scalarArray
     */
    protected function setScalarArray(array $scalarArray): void
    {
        $this->scalarArray = $scalarArray;
    }
}
