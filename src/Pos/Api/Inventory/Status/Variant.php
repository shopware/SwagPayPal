<?php declare(strict_types=1);
/*
 * (c) shopware AG <info@shopware.com>
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Swag\PayPal\Pos\Api\Inventory\Status;

use Swag\PayPal\Pos\Api\Common\PosStruct;

class Variant extends PosStruct
{
    /**
     * @var string
     */
    protected $locationUuid;

    /**
     * @var string
     */
    protected $locationType;

    /**
     * @var string
     */
    protected $productUuid;

    /**
     * @var string
     */
    protected $variantUuid;

    /**
     * @var int
     */
    protected $balance;

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

    public function setBalance(string $balance): void
    {
        $this->balance = (int) $balance;
    }
}
