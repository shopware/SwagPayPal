<?php declare(strict_types=1);
/*
 * (c) shopware AG <info@shopware.com>
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Swag\PayPal\Pos\Api\Inventory\BulkChanges\ProductChange;

use Swag\PayPal\Pos\Api\Common\PosStruct;

class VariantChange extends PosStruct
{
    /**
     * @deprecated tag:v4.0.0 - will be strongly typed
     *
     * @var string
     */
    protected $productUuid;

    /**
     * @deprecated tag:v4.0.0 - will be strongly typed
     *
     * @var string
     */
    protected $variantUuid;

    /**
     * @deprecated tag:v4.0.0 - will be strongly typed
     *
     * @var string
     */
    protected $fromLocationUuid;

    /**
     * @deprecated tag:v4.0.0 - will be strongly typed
     *
     * @var string
     */
    protected $toLocationUuid;

    /**
     * @deprecated tag:v4.0.0 - will be strongly typed
     *
     * @var int
     */
    protected $change;

    public function getProductUuid(): string
    {
        return $this->productUuid;
    }

    public function setProductUuid(string $productUuid): void
    {
        $this->productUuid = $productUuid;
    }

    public function getVariantUuid(): string
    {
        return $this->variantUuid;
    }

    public function setVariantUuid(string $variantUuid): void
    {
        $this->variantUuid = $variantUuid;
    }

    public function getFromLocationUuid(): string
    {
        return $this->fromLocationUuid;
    }

    public function setFromLocationUuid(string $fromLocationUuid): void
    {
        $this->fromLocationUuid = $fromLocationUuid;
    }

    public function getToLocationUuid(): string
    {
        return $this->toLocationUuid;
    }

    public function setToLocationUuid(string $toLocationUuid): void
    {
        $this->toLocationUuid = $toLocationUuid;
    }

    public function getChange(): int
    {
        return $this->change;
    }

    public function setChange(int $change): void
    {
        $this->change = $change;
    }
}
