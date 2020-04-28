<?php declare(strict_types=1);
/*
 * (c) shopware AG <info@shopware.com>
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Swag\PayPal\IZettle\Api\Product;

use Swag\PayPal\IZettle\Api\Common\IZettleStruct;
use Swag\PayPal\IZettle\Api\Product\VariantOptionDefinitions\Definition;

class VariantOptionDefinitions extends IZettleStruct
{
    /**
     * @var Definition[]
     */
    protected $definitions = [];

    public function addDefinition(Definition ...$definitions): void
    {
        $this->definitions = \array_merge($this->definitions, $definitions);
    }

    /**
     * @param Definition[] $definitions
     */
    protected function setDefinitions(array $definitions): void
    {
        $this->definitions = $definitions;
    }
}
