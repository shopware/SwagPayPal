<?php declare(strict_types=1);
/*
 * (c) shopware AG <info@shopware.com>
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Swag\PayPal\Checkout\SalesChannel\Struct;

use Shopware\Core\Framework\Log\Package;
use Shopware\Core\Framework\Struct\Struct;

#[Package('checkout')]
abstract class SDKStruct extends Struct
{
    public function assign(array $options): static
    {
        foreach ($options as $key => $value) {
            if (!is_array($value)) {
                continue;
            }

            if (!$this->isAssociativeArray($value)) {
                continue;
            }

            $className = $this->getPropertyClassType($key);
            if ($className === null) {
                continue;
            }

            $this->$key = (new $className())->assign($value);
        }

        return parent::assign($options);
    }

    private function isAssociativeArray(array $value): bool
    {
        return \array_keys($value) !== \range(0, \count($value) - 1);
    }

    private function getPropertyClassType(string $camelCaseKey): ?string
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
        if (!\class_exists($name) || !\is_a($name, self::class, true)) {
            return null;
        }

        return $name;
    }
}
