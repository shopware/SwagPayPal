<?php declare(strict_types=1);
/*
 * (c) shopware AG <info@shopware.com>
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Swag\PayPal\Pos\Api\Product;

use Shopware\Core\Framework\Log\Package;
use Swag\PayPal\Pos\Api\Common\PosStruct;
use Swag\PayPal\Pos\Api\Product\Metadata\Source;

#[Package('checkout')]
class Metadata extends PosStruct
{
    protected bool $inPos;

    protected Source $source;

    public function isInPos(): bool
    {
        return $this->inPos;
    }

    public function setInPos(bool $inPos): void
    {
        $this->inPos = $inPos;
    }

    public function getSource(): Source
    {
        return $this->source;
    }

    public function setSource(Source $source): void
    {
        $this->source = $source;
    }
}
