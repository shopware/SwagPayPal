<?php declare(strict_types=1);
/*
 * (c) shopware AG <info@shopware.com>
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Swag\PayPal\Pos\Sync\Inventory\Calculator;

use Shopware\Core\Content\Product\ProductEntity;
use Swag\PayPal\Pos\Sync\Context\InventoryContext;

class LocalWebhookCalculator implements LocalCalculatorInterface
{
    /**
     * @var int[]
     */
    private $fixedUpdates;

    public function addFixedUpdate(string $productId, int $amount): void
    {
        $this->fixedUpdates[$productId] = $amount;
    }

    public function getChangeAmount(ProductEntity $productEntity, InventoryContext $inventoryContext): int
    {
        if (!isset($this->fixedUpdates[$productEntity->getId()])) {
            return 0;
        }

        return $this->fixedUpdates[$productEntity->getId()];
    }
}
