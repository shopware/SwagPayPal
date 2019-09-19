<?php declare(strict_types=1);

namespace Swag\PayPal\PayPal\Api\Common;

use Symfony\Component\Serializer\NameConverter\CamelCaseToSnakeCaseNameConverter;

abstract class PayPalStruct implements \JsonSerializable
{
    public function assign(array $arrayDataWithSnakeCaseKeys): self
    {
        $nameConverter = new CamelCaseToSnakeCaseNameConverter();

        foreach ($arrayDataWithSnakeCaseKeys as $snakeCaseKey => $value) {
            $camelCaseKey = ucfirst($nameConverter->denormalize($snakeCaseKey));
            $setterMethod = 'set' . $camelCaseKey;
            if (!method_exists($this, $setterMethod)) {
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
                $className = $namespace . $camelCaseKey;
                if (!class_exists($className)) {
                    continue;
                }

                $instance = $this->createNewAssociation($className, $value);
                $this->$setterMethod($instance);
                continue;
            }

            $arrayWithToManyAssociations = [];
            /** @var array $value */
            foreach ($value as $toManyAssociation) {
                if ($toManyAssociation === null) {
                    continue;
                }

                $className = $namespace . $this->getClassNameOfOneToManyAssociation($camelCaseKey);
                if (!class_exists($className)) {
                    continue;
                }

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

        foreach (get_object_vars($this) as $property => $value) {
            $snakeCasePropertyName = $nameConverter->normalize($property);

            $data[$snakeCasePropertyName] = $value;
        }

        return $data;
    }

    private function isScalar($value): bool
    {
        return !\is_array($value);
    }

    private function isAssociativeArray(array $value): bool
    {
        if ($value === []) {
            return false;
        }

        return array_keys($value) !== range(0, \count($value) - 1);
    }

    private function getNamespaceOfAssociation(): string
    {
        return \get_class($this) . '\\';
    }

    private function getClassNameOfOneToManyAssociation(string $camelCaseKey): string
    {
        return rtrim($camelCaseKey, 's');
    }

    private function createNewAssociation(string $className, array $value): self
    {
        /** @var self $instance */
        $instance = new $className();
        $instance->assign($value);

        return $instance;
    }
}
