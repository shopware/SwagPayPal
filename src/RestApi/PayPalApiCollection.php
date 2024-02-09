<?php declare(strict_types=1);
/*
 * (c) shopware AG <info@shopware.com>
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Swag\PayPal\RestApi;

use Shopware\Core\Framework\Log\Package;

/**
 * @template TElement of PayPalApiStruct
 *
 * @implements \IteratorAggregate<array-key, TElement>
 */
#[Package('checkout')]
abstract class PayPalApiCollection implements \IteratorAggregate, \Countable, \JsonSerializable
{
    /**
     * @var array<array-key, TElement>
     */
    protected array $elements = [];

    /**
     * @param array<TElement> $elements
     */
    final public function __construct(iterable $elements = [])
    {
        foreach ($elements as $key => $element) {
            $this->set($key, $element);
        }
    }

    /**
     * @param TElement $element
     */
    public function set(string|int $key, PayPalApiStruct $element): void
    {
        $this->validateType($element);

        $this->elements[$key] = $element;
    }

    /**
     * @return class-string<TElement>
     */
    abstract public static function getExpectedClass(): string;

    public static function createFromAssociative(array $associativeData): static
    {
        $collection = new static();
        foreach (\array_filter($associativeData) as $value) {
            $collection->add((new (static::getExpectedClass())())->assign($value));
        }

        return $collection;
    }

    /**
     * @param TElement $element
     */
    public function add(PayPalApiStruct $element): void
    {
        $this->validateType($element);

        $this->elements[] = $element;
    }

    /**
     * @return TElement|null
     */
    public function get(string|int $key): ?PayPalApiStruct
    {
        if ($this->has($key)) {
            return $this->elements[$key];
        }

        return null;
    }

    public function has(string|int $key): bool
    {
        return \array_key_exists($key, $this->elements);
    }

    public function clear(): void
    {
        $this->elements = [];
    }

    public function count(): int
    {
        return \count($this->elements);
    }

    /**
     * @return list<array-key>
     */
    public function getKeys(): array
    {
        return \array_keys($this->elements);
    }

    public function reduce(\Closure $closure, mixed $initial = null): mixed
    {
        return \array_reduce($this->elements, $closure, $initial);
    }

    /**
     * @return array<array-key, mixed>
     */
    public function fmap(\Closure $closure): array
    {
        return \array_filter($this->map($closure));
    }

    public function map(\Closure $closure): array
    {
        return \array_map($closure, $this->elements);
    }

    public function sort(\Closure $closure): void
    {
        \uasort($this->elements, $closure);
    }

    public function filter(\Closure $closure): static
    {
        return $this->createNew(\array_filter($this->elements, $closure));
    }

    public function slice(int $offset, ?int $length = null): static
    {
        return $this->createNew(\array_slice($this->elements, $offset, $length, true));
    }

    /**
     * @return array<TElement>
     */
    public function getElements(): array
    {
        return $this->elements;
    }

    /**
     * @return list<TElement>
     */
    public function jsonSerialize(): array
    {
        return \array_values($this->elements);
    }

    /**
     * @return TElement|null
     */
    public function first(): ?PayPalApiStruct
    {
        return \array_values($this->elements)[0] ?? null;
    }

    /**
     * @return TElement|null
     */
    public function getAt(int $position): ?PayPalApiStruct
    {
        return \array_values($this->elements)[$position] ?? null;
    }

    /**
     * @return TElement|null
     */
    public function last(): ?PayPalApiStruct
    {
        return \array_values($this->elements)[\count($this->elements) - 1] ?? null;
    }

    public function remove(string|int $key): void
    {
        unset($this->elements[$key]);
    }

    /**
     * @return \Generator<TElement>
     */
    public function getIterator(): \Traversable
    {
        yield from $this->elements;
    }

    /**
     * @param TElement $element
     */
    protected function validateType(PayPalApiStruct $element): void
    {
        $expectedClass = static::getExpectedClass();

        if (!$element instanceof $expectedClass) {
            throw new \InvalidArgumentException(
                \sprintf('Expected collection element of type %s got %s', $expectedClass, $element::class)
            );
        }
    }

    /**
     * @param iterable<TElement> $elements
     */
    protected function createNew(iterable $elements = []): static
    {
        return new static($elements);
    }
}
