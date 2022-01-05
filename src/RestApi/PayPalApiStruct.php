<?php declare(strict_types=1);
/*
 * (c) shopware AG <info@shopware.com>
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Swag\PayPal\RestApi;

use Symfony\Component\Serializer\NameConverter\CamelCaseToSnakeCaseNameConverter;

abstract class PayPalApiStruct implements \JsonSerializable
{
    final public function __construct()
    {
    }

    /**
     * @return static
     */
    public function assign(array $arrayDataWithSnakeCaseKeys)
    {
        $nameConverter = new CamelCaseToSnakeCaseNameConverter();

        foreach ($arrayDataWithSnakeCaseKeys as $snakeCaseKey => $value) {
            if ($value === [] || $value === null) {
                continue;
            }

            $camelCaseKey = \ucfirst($nameConverter->denormalize($snakeCaseKey));
            $setterMethod = \sprintf('set%s', $camelCaseKey);
            if (!\method_exists($this, $setterMethod)) {
                // There is no setter/property for a given data key from PayPal.
                // Continue here to not break the plugin, if the plugin is not up-to-date with the PayPal API
                continue;
            }

            if ($this->isScalar($value)) {
                $this->$setterMethod($value);

                continue;
            }

            $namespace = $this->getNamespaceOfAssociation();
            if ($this->isAssociativeArray($value)) {
                /** @var class-string<PayPalApiStruct> $className */
                $className = $namespace . $camelCaseKey;
                if (!\class_exists($className)) {
                    continue;
                }

                $instance = $this->createNewAssociation($className, $value);
                $this->$setterMethod($instance);

                continue;
            }

            // Value is not a list of objects
            if (!\is_array($value[0])) {
                $this->$setterMethod($value);

                continue;
            }

            /** @var class-string<PayPalApiStruct> $className */
            $className = $namespace . $this->getClassNameOfOneToManyAssociation($camelCaseKey);
            if (!\class_exists($className)) {
                continue;
            }

            $arrayWithToManyAssociations = [];
            foreach ($value as $toManyAssociation) {
                $instance = $this->createNewAssociation($className, $toManyAssociation);
                $arrayWithToManyAssociations[] = $instance;
            }
            $this->$setterMethod($arrayWithToManyAssociations);
        }

        return $this;
    }

    public function jsonSerialize(): array
    {
        $data = [];
        $nameConverter = new CamelCaseToSnakeCaseNameConverter();

        foreach (\array_keys(\get_class_vars(static::class)) as $property) {
            $snakeCasePropertyName = $nameConverter->normalize($property);

            try {
                $data[$snakeCasePropertyName] = $this->$property;
                /* @phpstan-ignore-next-line */
            } catch (\Error $error) {
            }
        }

        return $data;
    }

    /**
     * @param int|string|bool|array|PayPalApiStruct|null $value
     */
    private function isScalar($value): bool
    {
        return !\is_array($value);
    }

    private function isAssociativeArray(array $value): bool
    {
        return \array_keys($value) !== \range(0, \count($value) - 1);
    }

    private function getNamespaceOfAssociation(): string
    {
        return \sprintf('%s\\', static::class);
    }

    private function getClassNameOfOneToManyAssociation(string $camelCaseKey): string
    {
        return \rtrim($camelCaseKey, 's');
    }

    /**
     * @psalm-param class-string<PayPalApiStruct> $className
     */
    private function createNewAssociation(string $className, array $value): self
    {
        $instance = new $className();
        $instance->assign($value);

        return $instance;
    }
}
