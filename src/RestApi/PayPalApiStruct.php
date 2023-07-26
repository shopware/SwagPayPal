<?php declare(strict_types=1);
/*
 * (c) shopware AG <info@shopware.com>
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Swag\PayPal\RestApi;

use Shopware\Core\Framework\Log\Package;
use Symfony\Component\Serializer\NameConverter\CamelCaseToSnakeCaseNameConverter;

#[Package('checkout')]
abstract class PayPalApiStruct implements \JsonSerializable
{
    final public function __construct()
    {
    }

    /**
     * @param array<string, mixed> $arrayDataWithSnakeCaseKeys
     */
    public function assign(array $arrayDataWithSnakeCaseKeys): static
    {
        $nameConverter = new CamelCaseToSnakeCaseNameConverter();

        foreach ($arrayDataWithSnakeCaseKeys as $snakeCaseKey => $value) {
            if ($value === [] || $value === null) {
                continue;
            }

            $propertyName = $nameConverter->denormalize($snakeCaseKey);
            $setterMethod = \sprintf('set%s', \ucfirst($propertyName));
            if (!\method_exists($this, $setterMethod)) {
                // There is no setter/property for a given data key from PayPal.
                // Continue here to not break the plugin, if the plugin is not up-to-date with the PayPal API
                continue;
            }

            if ($this->isScalar($value)) {
                $this->$setterMethod($value);

                continue;
            }

            /** @var class-string<PayPalApiStruct> $className */
            if ($this->isAssociativeArray($value) && $className = $this->getPropertyType($propertyName)) {
                $this->$setterMethod((new $className())->assign($value));

                continue;
            }

            /** @var class-string<PayPalApiCollection<PayPalApiStruct>> $collectionClass */
            if ($collectionClass = $this->getCollection($propertyName)) {
                $this->$setterMethod($collectionClass::createFromAssociative($value));

                continue;
            }

            // try for scalar value arrays like string[]
            $this->$setterMethod($value);
        }

        return $this;
    }

    public function jsonSerialize(): array
    {
        $data = [];
        $nameConverter = new CamelCaseToSnakeCaseNameConverter();

        foreach (\array_keys(\get_class_vars(static::class)) as $property) {
            $snakeCasePropertyName = $nameConverter->normalize($property);

            if ((new \ReflectionProperty($this, $property))->isInitialized($this)) {
                $data[$snakeCasePropertyName] = $this->$property;
            }
        }

        return $data;
    }

    public function unset(string $propertyName): void
    {
        unset($this->$propertyName);
    }

    public function isset(string $propertyName): bool
    {
        return isset($this->$propertyName);
    }

    private function isScalar(mixed $value): bool
    {
        return !\is_array($value);
    }

    private function isAssociativeArray(array $value): bool
    {
        return \array_keys($value) !== \range(0, \count($value) - 1);
    }

    /**
     * @return class-string<PayPalApiStruct>|null
     */
    private function getPropertyType(string $camelCaseKey): ?string
    {
        return $this->getPropertyClassType($camelCaseKey, self::class);
    }

    /**
     * @return class-string<PayPalApiCollection<PayPalApiStruct>>|null
     */
    private function getCollection(string $camelCaseKey): ?string
    {
        return $this->getPropertyClassType($camelCaseKey, PayPalApiCollection::class);
    }

    /**
     * @template T of string
     *
     * @param T $expectedClass
     *
     * @return T|null
     */
    private function getPropertyClassType(string $camelCaseKey, string $expectedClass): ?string
    {
        $property = new \ReflectionProperty($this, $camelCaseKey);
        $type = $property->getType();
        if (!$type instanceof \ReflectionNamedType) {
            return null;
        }

        if ($type->isBuiltin()) {
            return null;
        }

        $name = $type->getName();
        if (!\class_exists($name)) {
            return null;
        }

        if (!\is_a($name, $expectedClass, true)) {
            return null;
        }

        // @phpstan-ignore-next-line  phpstan does not understand class-strings as template types
        return $name;
    }
}
