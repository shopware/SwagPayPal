<?php declare(strict_types=1);
/*
 * (c) shopware AG <info@shopware.com>
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Swag\PayPal\Checkout\PUI\MessageQueue;

use Shopware\Core\Framework\Log\Package;
use Shopware\Core\Framework\MessageQueue\AsyncMessageInterface;
use Shopware\Core\Framework\Struct\JsonSerializableTrait;

#[Package('checkout')]
class PUIInstructionsFetchMessage implements AsyncMessageInterface, \JsonSerializable
{
    use JsonSerializableTrait;

    public function __construct(
        protected string $transactionId,
    ) {
    }

    public function getTransactionId(): string
    {
        return $this->transactionId;
    }
}
