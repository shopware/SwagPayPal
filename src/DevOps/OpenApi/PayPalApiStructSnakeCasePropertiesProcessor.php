<?php declare(strict_types=1);
/*
 * (c) shopware AG <info@shopware.com>
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Swag\PayPal\DevOps\OpenApi;

use OpenApi\Analysis;
use OpenApi\Annotations as OA;
use OpenApi\Generator;
use Shopware\Core\Framework\Log\Package;
use Swag\PayPal\RestApi\PayPalApiStruct;

#[Package('checkout')]
class PayPalApiStructSnakeCasePropertiesProcessor
{
    public function __invoke(Analysis $analysis): void
    {
        /** @var OA\Property[] $properties */
        $properties = $analysis->getAnnotationsOfType(OA\Property::class);

        foreach ($properties as $property) {
            if (Generator::isDefault($property->property) || !$property->_context?->namespace || !$property->_context->class || !$property->_context->property) {
                continue;
            }

            $fqdn = $property->_context->namespace . '\\' . $property->_context->class;

            if (!\class_exists($fqdn)) {
                $property->_context->logger?->error(\sprintf('Class %s does not exist', $fqdn));
                continue;
            }

            if ((new \ReflectionClass($fqdn))->isSubclassOf(PayPalApiStruct::class)) {
                $property->property = \mb_strtolower(\preg_replace('/[A-Z]/', '_\\0', $property->_context->property) ?? '');
            }
        }
    }
}
