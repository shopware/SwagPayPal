<?php declare(strict_types=1);
/*
 * (c) shopware AG <info@shopware.com>
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Swag\PayPal\Pos\Api\Common;

use Shopware\Core\Framework\Log\Package;

#[Package('checkout')]
abstract class PosStruct implements \JsonSerializable
{
    final public function __construct()
    {
    }

    /**
     * @param array<string, mixed> $arrayData
     *
     * @return static
     */
    public function assign(array $arrayData)
    {
        foreach ($arrayData as $key => $value) {
            $camelCaseKey = $this->toCamelCase($key);
            $setterMethod = 'set' . $camelCaseKey;
            if (!\method_exists($this, $setterMethod)) {
                // There is no setter/property for a given data key from PayPal.
                // Continue here to not break the plugin, if the plugin is not up-to-date with the PayPal API
                continue;
            }

            if ($this->isScalar($value)) {
                if ($value !== null) {
                    $this->$setterMethod($value);
                }

                continue;
            }

            $namespace = $this->getNamespaceOfAssociation();
            if ($value !== [] && $this->isAssociativeArray($value)) {
                /** @var class-string<PosStruct> $className */
                $className = $namespace . $camelCaseKey;
                if (!\class_exists($className)) {
                    continue;
                }

                $instance = $this->createNewAssociation($className, $value);
                $this->$setterMethod($instance);

                continue;
            }

            /** @var class-string<PosStruct> $className */
            $className = $namespace . $this->getClassNameOfOneToManyAssociation($camelCaseKey);
            if (!\class_exists($className)) {
                $arrayData = \array_filter(
                    $value,
                    /** @param string|array|null $var */
                    static function ($var) {
                        return $var !== null;
                    }
                );
                $this->$setterMethod($arrayData);

                continue;
            }

            $arrayWithToManyAssociations = [];
            foreach ($value as $toManyAssociation) {
                if ($toManyAssociation === null) {
                    continue;
                }

                $instance = $this->createNewAssociation($className, $toManyAssociation);
                $arrayWithToManyAssociations[] = $instance;
            }
            $this->$setterMethod($arrayWithToManyAssociations);
        }

        return $this;
    }

    /**
     * @return array<string, mixed>
     */
    public function jsonSerialize(): array
    {
        $data = [];

        foreach (\array_keys(\get_class_vars(static::class)) as $property) {
            try {
                $data[$property] = $this->$property;
                /* @phpstan-ignore-next-line */
            } catch (\Error $error) {
                $data[$property] = null;
            }
        }

        return $data;
    }

    /**
     * @param int|string|bool|array|PosStruct|null $value
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
        return static::class . '\\';
    }

    private function getClassNameOfOneToManyAssociation(string $camelCaseKey): string
    {
        return \rtrim($camelCaseKey, 's');
    }

    /**
     * @psalm-param class-string<PosStruct> $className
     */
    private function createNewAssociation(string $className, array $value): self
    {
        $instance = new $className();
        $instance->assign($value);

        return $instance;
    }

    private function toCamelCase(string $string): string
    {
        $string = \ucwords(\str_replace(['-', '_'], ' ', $string));
        $string = \str_replace(' ', '', $string);

        return $string;
    }
}
