<?php declare(strict_types=1);
/*
 * (c) shopware AG <info@shopware.com>
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Swag\PayPal\Pos\Api\Service\Converter;

use Shopware\Core\Content\Property\PropertyGroupCollection;
use Shopware\Core\Framework\Log\Package;
use Swag\PayPal\Pos\Api\Product\Variant;
use Swag\PayPal\Pos\Api\Product\VariantOptionDefinitions;
use Swag\PayPal\Pos\Api\Product\VariantOptionDefinitions\Definition;
use Swag\PayPal\Pos\Api\Product\VariantOptionDefinitions\Definition\Property;

#[Package('checkout')]
class OptionGroupConverter
{
    public function convert(PropertyGroupCollection $propertyGroupCollection): VariantOptionDefinitions
    {
        $variantOptionDefinitions = new VariantOptionDefinitions();

        foreach ($propertyGroupCollection as $groupedOption) {
            $variantOption = new Definition();
            $variantOption->setName($groupedOption->getTranslation('name') ?? $groupedOption->getName() ?? '');

            $options = $groupedOption->getOptions();
            if (!$options) {
                continue;
            }

            foreach ($options as $option) {
                $variantOptionProperty = new Property();
                $variantOptionProperty->setValue($option->getTranslation('name') ?? $option->getName() ?? '');
                $variantOption->addProperty($variantOptionProperty);
            }

            $variantOptionDefinitions->addDefinition($variantOption);
        }

        return $variantOptionDefinitions;
    }

    public function convertFromVariants(Variant ...$variants): VariantOptionDefinitions
    {
        $groups = [];
        foreach ($variants as $variant) {
            $options = $variant->getOptions();
            if ($options) {
                foreach ($options as $option) {
                    $optionName = $option->getName();
                    if (!isset($groups[$optionName])) {
                        $groups[$optionName] = [];
                    }
                    $groups[$optionName][] = $option->getValue();
                }
            }
        }

        $variantOptionDefinitions = new VariantOptionDefinitions();

        foreach ($groups as $groupName => $group) {
            $definition = new Definition();
            $definition->setName($groupName);
            foreach (\array_unique($group) as $option) {
                $property = new Property();
                $property->setValue($option);
                $definition->addProperty($property);
            }
            $variantOptionDefinitions->addDefinition($definition);
        }

        return $variantOptionDefinitions;
    }
}
