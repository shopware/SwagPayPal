<?php declare(strict_types=1);
/*
 * (c) shopware AG <info@shopware.com>
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Swag\PayPal\Pos\Api\Inventory;

use Shopware\Core\Framework\Log\Package;
use Swag\PayPal\Pos\Api\Common\PosStruct;
use Swag\PayPal\Pos\Api\Inventory\Status\Variant;

#[Package('checkout')]
class Status extends PosStruct
{
    protected ?string $locationUuid;

    /**
     * @var string[]
     */
    protected array $trackedProducts;

    /**
     * @var Variant[]
     */
    protected array $variants = [];

    public function getLocationUuid(): ?string
    {
        return $this->locationUuid;
    }

    public function setLocationUuid(?string $locationUuid): void
    {
        $this->locationUuid = $locationUuid;
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

    /**
     * @return Variant[]
     */
    public function getVariants(): array
    {
        return $this->variants;
    }

    /**
     * @param Variant[] $variants
     */
    public function setVariants(array $variants): void
    {
        $this->variants = $variants;
    }

    public function addVariant(Variant ...$variants): void
    {
        foreach ($variants as $variant) {
            $this->variants[] = $variant;
        }
    }
}
