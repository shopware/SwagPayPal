<?php declare(strict_types=1);
/*
 * (c) shopware AG <info@shopware.com>
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Swag\PayPal\Pos\Api\Product\VariantOptionDefinitions;

use Shopware\Core\Framework\Log\Package;
use Swag\PayPal\Pos\Api\Common\PosStruct;
use Swag\PayPal\Pos\Api\Product\VariantOptionDefinitions\Definition\Property;

#[Package('checkout')]
class Definition extends PosStruct
{
    protected string $name;

    /**
     * @var Property[]
     */
    protected array $properties = [];

    public function setName(string $name): void
    {
        $this->name = $name;
    }

    public function addProperty(Property ...$properties): void
    {
        $this->properties = \array_merge($this->properties, $properties);
    }

    public function getProperties(): array
    {
        return $this->properties;
    }

    /**
     * @param Property[] $properties
     *
     * @deprecated tag:v10.0.0 - Use setProperties instead
     */
    public function setPropertys(array $properties): void
    {
        $this->properties = $properties;
    }

    /**
     * @param Property[] $properties
     */
    public function setProperties(array $properties): void
    {
        $this->properties = $properties;
    }
}
