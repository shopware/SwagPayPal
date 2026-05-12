<?php declare(strict_types=1);
/*
 * (c) shopware AG <info@shopware.com>
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Swag\PayPal\Pos\Api\Product;

use Shopware\Core\Framework\Log\Package;
use Swag\PayPal\Pos\Api\Common\PosStruct;
use Swag\PayPal\Pos\Api\Product\VariantOptionDefinitions\Definition;

#[Package('checkout')]
class VariantOptionDefinitions extends PosStruct
{
    /**
     * @var Definition[]
     */
    protected array $definitions = [];

    public function getDefinitions(): array
    {
        return $this->definitions;
    }

    public function setDefinitions(array $definitions): void
    {
        $this->definitions = $definitions;
    }

    public function addDefinition(Definition ...$definitions): void
    {
        $this->definitions = \array_merge($this->definitions, $definitions);
    }
}
