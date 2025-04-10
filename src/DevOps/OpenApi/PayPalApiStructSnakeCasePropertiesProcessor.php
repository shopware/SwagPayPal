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
use Shopware\PayPalSDK\Struct\Struct;
use Shopware\PayPalSDK\Util\CaseConverter;

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

            if ((new \ReflectionClass($fqdn))->isSubclassOf(Struct::class)) {
                $property->property = CaseConverter::normalize($property->_context->property, true);
            }
        }
    }
}
