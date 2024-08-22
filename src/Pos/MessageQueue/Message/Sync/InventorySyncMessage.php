<?php declare(strict_types=1);
/*
 * (c) shopware AG <info@shopware.com>
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Swag\PayPal\Pos\MessageQueue\Message\Sync;

use Shopware\Core\Framework\Log\Package;
use Swag\PayPal\Pos\MessageQueue\Message\AbstractSyncMessage;
use Swag\PayPal\Pos\Sync\Context\InventoryContext;

#[Package('checkout')]
class InventorySyncMessage extends AbstractSyncMessage
{
    protected InventoryContext $inventoryContext;

    public function getInventoryContext(): InventoryContext
    {
        return $this->inventoryContext;
    }

    public function setInventoryContext(InventoryContext $inventoryContext): void
    {
        $this->inventoryContext = $inventoryContext;
    }
}
