<?php declare(strict_types=1);
/*
 * (c) shopware AG <info@shopware.com>
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Swag\PayPal\Test\RestApi;

use PHPUnit\Framework\TestCase;
use Swag\PayPal\RestApi\PayPalApiCollection;
use Swag\PayPal\RestApi\PayPalApiStruct;
use Swag\PayPal\RestApi\V1\Api\OAuthCredentials;
use Swag\PayPal\RestApi\V1\Api\Token;
use Symfony\Component\Finder\Finder;

/**
 * @internal
 */
class PayPalApiStructStructureTest extends TestCase
{
    private const EXCLUSIONS = [
        PayPalApiCollection::class,
        OAuthCredentials::class,
        Token::class,
    ];

    /**
     * @dataProvider dataProviderStructPaths
     */
    public function testAllStructsHaveSettersAndGetters(string $path): void
    {
        $structs = $this->getAllStructs($path);
        foreach ($structs as $structClass) {
            if ($this->isExcluded($structClass)) {
                continue;
            }

            $reflectionClass = new \ReflectionClass($structClass);
            if ($reflectionClass->isAbstract()) {
                continue;
            }

            $struct = new $structClass();
            static::assertInstanceOf(PayPalApiStruct::class, $struct);
            static::assertInstanceOf($structClass, $struct);

            $properties = $reflectionClass->getProperties();
            foreach ($properties as $property) {
                static::assertTrue($property->isProtected(), \sprintf('Property %s in class %s is not protected', $property->getName(), $structClass));
                $propertyName = \ucfirst(\str_replace('_', '', $property->getName()));
                $propertyType = $property->getType();
                $isBool = $propertyType instanceof \ReflectionNamedType && $propertyType->getName() === 'bool';
                $getter = ($isBool ? 'is' : 'get') . $propertyName;
                $setter = 'set' . $propertyName;
                static::assertTrue($reflectionClass->hasMethod($getter), \sprintf('Missing getter for property %s in class %s', $propertyName, $structClass));
                static::assertTrue($reflectionClass->hasMethod($setter), \sprintf('Missing setter for property %s in class %s', $propertyName, $structClass));

                $propertyTypeName = $this->getTypeName($propertyType);

                $reflectionGetter = $reflectionClass->getMethod($getter);
                static::assertSame($propertyTypeName, $this->getTypeName($reflectionGetter->getReturnType()), \sprintf('Getter for property %s in class %s has wrong return type', $propertyTypeName, $structClass));

                $reflectionSetter = $reflectionClass->getMethod($setter);
                $reflectionSetterParameters = $reflectionSetter->getParameters();
                static::assertCount(1, $reflectionSetterParameters, \sprintf('Setter for property %s in class %s has wrong number of parameters', $propertyName, $structClass));
                static::assertStringContainsString($propertyTypeName ?: '-', $this->getTypeName($reflectionSetterParameters[0]->getType()) ?: '', \sprintf('Setter for property %s in class %s has wrong parameter type', $propertyName, $structClass));

                $value = $this->getMockValue($propertyType);
                $struct->$setter($value);
                static::assertSame($value, $struct->$getter(), \sprintf('Getter for property %s in class %s does not return the same value as set', $propertyName, $structClass));
            }
        }
    }

    public static function dataProviderStructPaths(): array
    {
        $basePath = \dirname((new \ReflectionClass(PayPalApiStruct::class))->getFileName() ?: '');

        return [
            [$basePath . '/V1/Api'],
            [$basePath . '/V2/Api'],
            [$basePath . '/V3/Api'],
        ];
    }

    /**
     * @return class-string[]
     */
    private function getAllStructs(string $path): array
    {
        $finderFiles = Finder::create()->files()->in($path)->name('*.php');
        $classNames = [];
        foreach ($finderFiles as $finderFile) {
            $fileName = $finderFile->getRealpath();
            $className = $this->getFullNamespace($fileName) . '\\' . $this->getClassName($fileName);

            if (!\class_exists($className)) {
                continue;
            }

            $classNames[] = $className;
        }

        return $classNames;
    }

    private function getClassName(string $fileName): string
    {
        $directoriesAndFileName = \explode('/', $fileName);
        $fileName = \array_pop($directoriesAndFileName);
        $nameAndExtension = \explode('.', $fileName);

        return \array_shift($nameAndExtension);
    }

    private function getFullNamespace(string $fileName): string
    {
        $lines = \file($fileName) ?: [];
        $array = \preg_grep('/^namespace /', $lines) ?: [];
        $namespaceLine = \array_shift($array);
        $match = [];
        \preg_match('/^namespace (.*);$/', $namespaceLine, $match);

        return \array_pop($match) ?? '';
    }

    private function isExcluded(string $structClass): bool
    {
        foreach (self::EXCLUSIONS as $exclusion) {
            if (\is_a($structClass, $exclusion, true)) {
                return true;
            }
        }

        return false;
    }

    private function getTypeName(?\ReflectionType $type): ?string
    {
        if ($type === null) {
            return null;
        }

        if ($type instanceof \ReflectionUnionType) {
            return \implode('|', \array_map([$this, 'getTypeName'], $type->getTypes()));
        }

        if ($type instanceof \ReflectionNamedType) {
            return $type->getName();
        }

        return null;
    }

    private function getMockValue(?\ReflectionType $propertyType): mixed
    {
        if ($propertyType === null) {
            return null;
        }

        if ($propertyType instanceof \ReflectionUnionType) {
            $types = $propertyType->getTypes();
            $propertyType = $types[0];
        }

        return match ($propertyType->getName()) {
            'string' => 'test',
            'int' => 1,
            'float' => 1.0,
            'bool' => true,
            'array' => [],
            'object' => new \stdClass(),
            // @phpstan-ignore-next-line
            default => $this->createMock($propertyType->getName()),
        };
    }
}
