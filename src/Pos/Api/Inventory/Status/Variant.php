<?php declare(strict_types=1);
/*
 * (c) shopware AG <info@shopware.com>
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Swag\PayPal\Pos\Api\Inventory\Status;

use Shopware\Core\Framework\Log\Package;
use Swag\PayPal\Pos\Api\Common\PosStruct;

#[Package('checkout')]
class Variant extends PosStruct
{
    protected string $locationUuid;

    protected string $locationType;

    protected string $productUuid;

    protected string $variantUuid;

    protected int $balance;

    public function getLocationUuid(): string
    {
        return $this->locationUuid;
    }

    public function setLocationUuid(string $locationUuid): void
    {
        $this->locationUuid = $locationUuid;
    }

    public function getLocationType(): string
    {
        return $this->locationType;
    }

    public function setLocationType(string $locationType): void
    {
        $this->locationType = $locationType;
    }

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

    public function getBalance(): int
    {
        return $this->balance;
    }

    /**
     * @param int|string $balance
     */
    public function setBalance($balance): void
    {
        $this->balance = (int) $balance;
    }
}
