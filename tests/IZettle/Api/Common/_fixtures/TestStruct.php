<?php declare(strict_types=1);
/*
 * (c) shopware AG <info@shopware.com>
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Swag\PayPal\Test\IZettle\Api\Common\_fixtures;

use Swag\PayPal\IZettle\Api\Common\IZettleStruct;
use Swag\PayPal\Test\IZettle\Api\Common\_fixtures\TestStruct\Bar;
use Swag\PayPal\Test\IZettle\Api\Common\_fixtures\TestStruct\Foo;

class TestStruct extends IZettleStruct
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

    /**
     * @var object
     */
    protected $notExistingClass;

    /**
     * @var array
     */
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
