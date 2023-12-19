<?php declare(strict_types=1);
/*
 * (c) shopware AG <info@shopware.com>
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Swag\PayPal\Test\RestApi;

use PHPUnit\Framework\TestCase;
use Shopware\Core\Framework\Log\Package;
use Swag\PayPal\RestApi\PayPalApiCollection;
use Swag\PayPal\RestApi\V2\Api\Common\Address;
use Swag\PayPal\RestApi\V2\Api\Common\Money;

/**
 * @internal
 */
#[Package('checkout')]
class PayPalApiCollectionTest extends TestCase
{
    public function testConstructor(): void
    {
        $elements = [(new Money())->assign(['value' => '1']), (new Money())->assign(['value' => '2'])];
        $collection = new TestCollection($elements);

        static::assertEquals($elements, $collection->getElements());
    }

    public function testConstructorKeepingKeys(): void
    {
        $elements = ['z' => (new Money())->assign(['value' => '1']), 'y' => (new Money())->assign(['value' => '2'])];
        $collection = new TestCollection($elements);

        static::assertEquals($elements, $collection->getElements());
    }

    public function testClear(): void
    {
        $collection = new TestCollection();
        $collection->add((new Money())->assign(['value' => '1']));
        $collection->add((new Money())->assign(['value' => '2']));

        $collection->clear();
        static::assertEmpty($collection->getElements());
    }

    public function testCount(): void
    {
        $collection = new TestCollection();
        static::assertEquals(0, $collection->count());

        $collection->add((new Money())->assign(['value' => '1']));
        $collection->add((new Money())->assign(['value' => '2']));
        static::assertEquals(2, $collection->count());
    }

    public function testGetNumericKeys(): void
    {
        $collection = new TestCollection();
        static::assertEquals([], $collection->getKeys());

        $collection->add((new Money())->assign(['value' => '1']));
        $collection->add((new Money())->assign(['value' => '2']));
        static::assertEquals([0, 1], $collection->getKeys());
    }

    public function testHasWithNumericKey(): void
    {
        $collection = new TestCollection();
        static::assertFalse($collection->has(0));

        $collection->add((new Money())->assign(['value' => '1']));
        $collection->add((new Money())->assign(['value' => '2']));
        static::assertTrue($collection->has(0));
        static::assertTrue($collection->has(1));
    }

    public function testMap(): void
    {
        $collection = new TestCollection();
        $collection->map(function (): void {
            static::fail('map should not be called for empty collection');
        });

        $collection->add((new Money())->assign(['value' => '1']));
        $collection->add((new Money())->assign(['value' => '2']));
        $result = $collection->map(fn (Money $element) => $element->getValue() . '_test');
        static::assertEquals(['1_test', '2_test'], $result);
    }

    public function testFmap(): void
    {
        $collection = new TestCollection();
        $collection->fmap(function (): void {
            static::fail('fmap should not be called for empty collection');
        });

        $collection->add((new Money())->assign(['value' => '1']));
        $collection->add((new Money())->assign(['value' => '2']));
        $filtered = $collection->fmap(fn (Money $element) => $element->getValue() === '1' ? false : $element->getValue() . '_test');
        static::assertEquals([1 => '2_test'], $filtered);
    }

    public function testSort(): void
    {
        $collection = new TestCollection();

        $collection->sort(function (): void {
            static::fail('fmap should not be called for empty collection');
        });

        $collection->add((new Money())->assign(['value' => '3']));
        $collection->add((new Money())->assign(['value' => '1']));
        $collection->add((new Money())->assign(['value' => '2']));

        $collection->sort(fn (Money $a, Money $b) => \strcmp($a->getValue(), $b->getValue()));

        static::assertEquals([1, 2, 0], $collection->getKeys());
    }

    public function testFilter(): void
    {
        $collection = new TestCollection();
        $collection->filter(function (): void {
            static::fail('filter should not be called for empty collection');
        });

        $collection->add((new Money())->assign(['value' => '1']));
        $collection->add((new Money())->assign(['value' => '2']));
        $collection->add((new Money())->assign(['value' => '3']));

        $filtered = $collection->filter(fn (Money $element) => $element->getValue() !== '2');
        static::assertEquals([0, 2], $filtered->getKeys());
    }

    public function testSlice(): void
    {
        $collection = new TestCollection();
        static::assertEmpty($collection->slice(0)->getElements());

        $collection->add((new Money())->assign(['value' => '1']));
        $collection->add((new Money())->assign(['value' => '2']));
        $collection->add((new Money())->assign(['value' => '3']));

        static::assertEquals([1, 2], \array_keys($collection->slice(1)->getElements()));
        static::assertEquals([1], \array_keys($collection->slice(1, 1)->getElements()));
    }

    public function testGetElements(): void
    {
        $elements = [
            (new Money())->assign(['value' => '1']),
            (new Money())->assign(['value' => '2']),
        ];
        $collection = new TestCollection();
        static::assertEquals([], $collection->getElements());

        $collection->add($elements[0]);
        $collection->add($elements[1]);

        static::assertEquals($elements, $collection->getElements());
    }

    public function testJsonSerialize(): void
    {
        $elements = [
            (new Money())->assign(['value' => '1']),
            (new Money())->assign(['value' => '2']),
        ];
        $collection = new TestCollection();
        static::assertEquals(
            [],
            $collection->jsonSerialize()
        );

        $collection->add($elements[0]);
        $collection->add($elements[1]);

        static::assertEquals(
            $elements,
            $collection->jsonSerialize()
        );
    }

    public function testFirst(): void
    {
        $collection = new TestCollection();
        static::assertNull($collection->first());

        $firstElement = (new Money())->assign(['value' => '1']);
        $collection->add($firstElement);
        $collection->add((new Money())->assign(['value' => '2']));

        static::assertEquals($firstElement, $collection->first());
    }

    public function testLast(): void
    {
        $collection = new TestCollection();
        static::assertNull($collection->last());

        $collection->add((new Money())->assign(['value' => '1']));
        $lastElement = (new Money())->assign(['value' => '2']);
        $collection->add($lastElement);

        static::assertEquals($lastElement, $collection->last());
    }

    public function testGetAt(): void
    {
        $collection = new TestCollection();
        static::assertFalse($collection->has(0));

        $firstElement = (new Money())->assign(['value' => '1']);
        $collection->add($firstElement);
        $lastElement = (new Money())->assign(['value' => '2']);
        $collection->add($lastElement);
        static::assertEquals($firstElement, $collection->getAt(0));
        static::assertEquals($lastElement, $collection->getAt(1));
    }

    public function testAddInvalidType(): void
    {
        $collection = new TestCollection();
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('Expected collection element of type ' . Money::class . ' got ' . Address::class);
        // @phpstan-ignore-next-line
        $collection->add(new Address());
    }
}

/**
 * @internal
 *
 * @extends PayPalApiCollection<Money>
 */
#[Package('checkout')]
class TestCollection extends PayPalApiCollection
{
    public static function getExpectedClass(): string
    {
        return Money::class;
    }
}
