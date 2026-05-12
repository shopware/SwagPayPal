<?php declare(strict_types=1);
/*
 * (c) shopware AG <info@shopware.com>
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Swag\PayPal\Pos\Api\Inventory;

use Shopware\Core\Framework\Log\Package;
use Swag\PayPal\Pos\Api\Common\PosStruct;
use Swag\PayPal\Pos\Api\Inventory\BulkChanges\ProductChange;

#[Package('checkout')]
class BulkChanges extends PosStruct
{
    protected ?string $returnBalanceForLocationUuid = null;

    /**
     * @var ProductChange[]
     */
    protected array $productChanges = [];

    public function getReturnBalanceForLocationUuid(): ?string
    {
        return $this->returnBalanceForLocationUuid;
    }

    public function setReturnBalanceForLocationUuid(string $returnBalanceForLocationUuid): void
    {
        $this->returnBalanceForLocationUuid = $returnBalanceForLocationUuid;
    }

    /**
     * @return ProductChange[]
     */
    public function getProductChanges(): array
    {
        return $this->productChanges;
    }

    /**
     * @param ProductChange[] $productChanges
     */
    public function setProductChanges(array $productChanges): void
    {
        $this->productChanges = $productChanges;
    }

    public function addProductChange(ProductChange ...$productChanges): void
    {
        foreach ($productChanges as $productChange) {
            $foundExisting = false;
            foreach ($this->productChanges as $existingChange) {
                if ($existingChange->getProductUuid() === $productChange->getProductUuid()) {
                    $existingChange->addVariantChange(...$productChange->getVariantChanges());
                    if ($productChange->getTrackingStatusChange() === ProductChange::TRACKING_START) {
                        $existingChange->setTrackingStatusChange(ProductChange::TRACKING_START);
                    }
                    $foundExisting = true;

                    break;
                }
            }
            if (!$foundExisting) {
                $this->productChanges[] = $productChange;
            }
        }
    }
}
