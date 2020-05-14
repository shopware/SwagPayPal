<?php declare(strict_types=1);
/*
 * (c) shopware AG <info@shopware.com>
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Swag\PayPal\IZettle\Api\Inventory;

use Swag\PayPal\IZettle\Api\Common\IZettleStruct;
use Swag\PayPal\IZettle\Api\Inventory\Status\Variant;

class Status extends IZettleStruct
{
    /**
     * @var string
     */
    protected $locationUuid;

    /**
     * @var string[]
     */
    protected $trackedProducts;

    /**
     * @var Variant[]
     */
    protected $variants = [];

    /**
     * @return Variant[]
     */
    public function getVariants(): array
    {
        return $this->variants;
    }

    /**
     * @return string[]
     */
    public function getTrackedProducts(): array
    {
        return $this->trackedProducts;
    }

    /**
     * @param string[] $trackedProducts
     */
    public function setTrackedProducts(array $trackedProducts): void
    {
        $this->trackedProducts = $trackedProducts;
    }

    public function addVariant(Variant ...$variants): void
    {
        foreach ($variants as $variant) {
            $this->variants[] = $variant;
            $this->trackedProducts[] = $variant->getProductUuid();
        }
    }

    protected function setLocationUuid(string $locationUuid): void
    {
        $this->locationUuid = $locationUuid;
    }

    /**
     * @param Variant[] $variants
     */
    protected function setVariants(array $variants): void
    {
        $this->variants = $variants;
    }
}
