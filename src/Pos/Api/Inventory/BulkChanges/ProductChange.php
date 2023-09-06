<?php declare(strict_types=1);
/*
 * (c) shopware AG <info@shopware.com>
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Swag\PayPal\Pos\Api\Inventory\BulkChanges;

use Shopware\Core\Framework\Log\Package;
use Swag\PayPal\Pos\Api\Common\PosStruct;
use Swag\PayPal\Pos\Api\Inventory\BulkChanges\ProductChange\VariantChange;

#[Package('checkout')]
class ProductChange extends PosStruct
{
    public const TRACKING_START = 'START_TRACKING';
    public const TRACKING_STOP = 'STOP_TRACKING';
    public const TRACKING_NOCHANGE = 'NO_CHANGE';

    protected string $productUuid;

    protected string $trackingStatusChange;

    /**
     * @var VariantChange[]
     */
    protected array $variantChanges = [];

    public function getProductUuid(): string
    {
        return $this->productUuid;
    }

    public function setProductUuid(string $productUuid): void
    {
        $this->productUuid = $productUuid;
    }

    public function getTrackingStatusChange(): string
    {
        return $this->trackingStatusChange;
    }

    public function setTrackingStatusChange(string $trackingStatusChange): void
    {
        $this->trackingStatusChange = $trackingStatusChange;
    }

    /**
     * @return VariantChange[]
     */
    public function getVariantChanges(): array
    {
        return $this->variantChanges;
    }

    /**
     * @param VariantChange[] $variantChanges
     */
    public function setVariantChanges(array $variantChanges): void
    {
        $this->variantChanges = $variantChanges;
    }

    public function addVariantChange(VariantChange ...$changes): void
    {
        foreach ($changes as $change) {
            $this->variantChanges[] = $change;
        }
    }
}
