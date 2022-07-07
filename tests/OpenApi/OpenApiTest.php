<?php declare(strict_types=1);
/*
 * (c) shopware AG <info@shopware.com>
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Swag\PayPal\Test\OpenApi;

use Doctrine\Common\Annotations\AnnotationReader;
use OpenApi\Annotations\Property;
use OpenApi\Annotations\Schema;
use PHPUnit\Framework\TestCase;
use Shopware\Core\Framework\Test\TestCaseBase\KernelTestBehaviour;
use Swag\PayPal\RestApi\PayPalApiStruct;
use Symfony\Component\Finder\Finder;
use const OpenApi\UNDEFINED;

class OpenApiTest extends TestCase
{
    use KernelTestBehaviour;

    public function testAllEntitiesHaveOpenApiAnnotations(): void
    {
        $bundles = $this->getContainer()->getParameter('kernel.bundles_metadata');

        if (!\is_array($bundles)) {
            return;
        }

        if (!\array_key_exists('SwagPayPal', $bundles)) {
            return;
        }

        $swagPayPal = $bundles['SwagPayPal'];

        if (!\array_key_exists('path', $swagPayPal)) {
            return;
        }

        $swagPayPalPath = $swagPayPal['path'];

        $finder = new Finder();
        $finder->name('*.php');
        $finder->files()->in([__DIR__ . '/../../src/RestApi/V1/Api', __DIR__ . '/../../src/RestApi/V2/Api']);

        $reader = new AnnotationReader();

        foreach ($finder as $file) {
            $path = $file->getRealPath();
            if (!$path) {
                continue;
            }

            $path = \str_replace($swagPayPalPath, '', $path);
            $path = \str_replace('/', '\\', $path);
            $path = \str_replace('.php', '', $path);

            /** @var class-string<PayPalApiStruct> $className */
            $className = 'Swag\\PayPal' . $path;

            $class = new \ReflectionClass($className);

            if (!$class->isSubclassOf(PayPalApiStruct::class)) {
                continue;
            }

            /** @var \ReflectionProperty[] $properties */
            $properties = $this->getDeclaredProperties($class);

            // only check classes, which define own properties
            // other common classes like `Link` will use the schema of the abstract parent class
            if (\count($properties) > 0) {
                $schema = $reader->getClassAnnotation($class, Schema::class);

                static::assertNotNull(
                    $schema,
                    \sprintf("Class %s should have exact 1 @OA\Schema annotation", $className)
                );

                static::assertNotEquals(
                    UNDEFINED,
                    $schema->schema,
                    \sprintf("@OA\Schema annotation of class %s should have 'schema' attribute set", $className)
                );

                static::assertNotEmpty(
                    $schema->schema,
                    \sprintf("@OA\Schema annotation of class %s should have 'schema' attribute set", $className)
                );

                foreach ($properties as $property) {
                    $propertyAnnotation = $reader->getPropertyAnnotation($property, Property::class);

                    static::assertNotNull(
                        $propertyAnnotation,
                        \sprintf(
                            "Property %s of class %s should have exact 1 @OA\Property annotation",
                            $property->getName(),
                            $className
                        )
                    );
                }
            }
        }
    }

    /**
     * @param \ReflectionClass<PayPalApiStruct> $class
     */
    private function getDeclaredProperties(\ReflectionClass $class): array
    {
        return \array_filter($class->getProperties(), function (\ReflectionProperty $property) use ($class) {
            return $property->getDeclaringClass()->getName() === $class->getName();
        });
    }
}
