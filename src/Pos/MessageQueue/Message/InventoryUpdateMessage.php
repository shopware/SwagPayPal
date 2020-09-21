<?php declare(strict_types=1);
/*
 * (c) shopware AG <info@shopware.com>
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Swag\PayPal\Pos\MessageQueue\Message;

use Shopware\Core\Framework\Context;

class InventoryUpdateMessage
{
    /**
     * @var string[]
     */
    private $ids;

    /**
     * @var Context
     */
    private $context;

    public function getIds(): array
    {
        return $this->ids;
    }

    public function setIds(array $ids): void
    {
        $this->ids = $ids;
    }

    public function setContext(Context $context): void
    {
        $this->context = $context;
    }

    public function getContext(): Context
    {
        return $this->context;
    }
}
