<?php declare(strict_types=1);
/*
 * (c) shopware AG <info@shopware.com>
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Swag\PayPal\Pos\MessageQueue\Message;

use Shopware\Core\Framework\Context;
use Shopware\Core\Framework\Log\Package;
use Shopware\Core\Framework\MessageQueue\AsyncMessageInterface;
use Shopware\Core\Framework\Struct\JsonSerializableTrait;

#[Package('checkout')]
class InventoryUpdateMessage implements AsyncMessageInterface, \JsonSerializable
{
    use JsonSerializableTrait {
        jsonSerialize as traitJsonSerialize;
    }

    /**
     * @var string[]
     */
    private array $ids;

    private ?Context $context = null;

    public function getIds(): array
    {
        return $this->ids;
    }

    public function setIds(array $ids): void
    {
        $this->ids = $ids;
    }

    public function getContext(): Context
    {
        return $this->context = $this->context ?? Context::createDefaultContext();
    }

    public function jsonSerialize(): array
    {
        $value = $this->traitJsonSerialize();

        unset($value['context']);

        return $value;
    }
}
