<?php declare(strict_types=1);
/*
 * (c) shopware AG <info@shopware.com>
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Swag\PayPal\Test\Pos\Converter;

use PHPUnit\Framework\TestCase;
use Shopware\Core\Content\Property\Aggregate\PropertyGroupOption\PropertyGroupOptionCollection;
use Shopware\Core\Content\Property\Aggregate\PropertyGroupOption\PropertyGroupOptionEntity;
use Shopware\Core\Content\Property\PropertyGroupCollection;
use Shopware\Core\Content\Property\PropertyGroupEntity;
use Shopware\Core\Framework\Uuid\Uuid;
use Swag\PayPal\Pos\Api\Product\Variant;
use Swag\PayPal\Pos\Api\Product\VariantOptionDefinitions;
use Swag\PayPal\Pos\Api\Service\Converter\OptionGroupConverter;

/**
 * @internal
 */
class OptionGroupConverterTest extends TestCase
{
    public function testConvert(): void
    {
        $propertyGroupCollection = $this->getPropertyGroupCollection();
        $actual = $this->createOptionGroupConverter()->convert($propertyGroupCollection);
        $expected = $this->getExpectedOptionGroup();
        static::assertEquals($expected, $actual);
    }

    public function testConvertFromVariants(): void
    {
        $variants = $this->getVariants();
        $actual = $this->createOptionGroupConverter()->convertFromVariants(...$variants);
        $expected = $this->getExpectedOptionGroup();
        static::assertEquals($expected, $actual);
    }

    private function createOptionGroupConverter(): OptionGroupConverter
    {
        return new OptionGroupConverter();
    }

    private function getPropertyGroupCollection(): PropertyGroupCollection
    {
        $collection = new PropertyGroupCollection();
        $propertyGroup = new PropertyGroupEntity();
        $propertyGroup->setId(Uuid::randomHex());
        $propertyGroup->setName('TestGroupEmpty');
        $propertyGroup->addTranslated('name', 'Test Group Empty');
        $collection->add($propertyGroup);
        $propertyGroup = new PropertyGroupEntity();
        $propertyGroup->setId(Uuid::randomHex());
        $propertyGroup->setName('TestGroup');
        $propertyGroup->addTranslated('name', 'Test Group');
        $collection->add($propertyGroup);

        $options = new PropertyGroupOptionCollection();
        $option = new PropertyGroupOptionEntity();
        $option->setId(Uuid::randomHex());
        $option->setName('TestOption');
        $option->addTranslated('name', 'Test Option');
        $options->add($option);
        $option = new PropertyGroupOptionEntity();
        $option->setId(Uuid::randomHex());
        $option->setName('TestOption2');
        $option->addTranslated('name', 'Test Option 2');
        $options->add($option);
        $propertyGroup->setOptions($options);

        return $collection;
    }

    private function getExpectedOptionGroup(): VariantOptionDefinitions
    {
        $data = [
            'definitions' => [
                [
                    'name' => 'Test Group',
                    'propertys' => [
                        ['value' => 'Test Option'],
                        ['value' => 'Test Option 2'],
                    ],
                ],
            ],
        ];
        $variantOptionsDefinitions = new VariantOptionDefinitions();
        $variantOptionsDefinitions->assign($data);

        return $variantOptionsDefinitions;
    }

    private function getVariants(): array
    {
        $data = [
            [
                'options' => [
                    [
                        'name' => 'Test Group',
                        'value' => 'Test Option',
                    ],
                ],
            ],
            [
                'options' => [
                    [
                        'name' => 'Test Group',
                        'value' => 'Test Option 2',
                    ],
                ],
            ],
        ];
        $variants = [];
        foreach ($data as $variantData) {
            $variant = new Variant();
            $variant->assign($variantData);
            $variants[] = $variant;
        }

        return $variants;
    }
}
