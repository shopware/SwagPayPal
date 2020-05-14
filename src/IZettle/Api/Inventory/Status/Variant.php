<?php declare(strict_types=1);
/*
 * (c) shopware AG <info@shopware.com>
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Swag\PayPal\IZettle\Api\Inventory\Status;

use Swag\PayPal\IZettle\Api\Common\IZettleStruct;

class Variant extends IZettleStruct
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

    public function getProductUuid(): string
    {
        return $this->productUuid;
    }

    public function getVariantUuid(): string
    {
        return $this->variantUuid;
    }

    public function getBalance(): int
    {
        return $this->balance;
    }

    public function setBalance(string $balance): void
    {
        $this->balance = (int) $balance;
    }

    protected function setLocationUuid(string $locationUuid): void
    {
        $this->locationUuid = $locationUuid;
    }

    protected function setLocationType(string $locationType): void
    {
        $this->locationType = $locationType;
    }

    protected function setProductUuid(string $productUuid): void
    {
        $this->productUuid = $productUuid;
    }

    protected function setVariantUuid(string $variantUuid): void
    {
        $this->variantUuid = $variantUuid;
    }
}
