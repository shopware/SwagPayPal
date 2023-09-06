<?php declare(strict_types=1);
/*
 * (c) shopware AG <info@shopware.com>
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Swag\PayPal\Pos\MessageQueue\Manager;

use Shopware\Core\Framework\Context;
use Shopware\Core\Framework\Log\Package;
use Shopware\Core\System\SalesChannel\SalesChannelEntity;
use Swag\PayPal\Pos\MessageQueue\Message\AbstractSyncMessage;
use Swag\PayPal\Pos\MessageQueue\MessageDispatcher;

#[Package('checkout')]
abstract class AbstractSyncManager
{
    protected MessageDispatcher $messageBus;

    /**
     * @internal
     */
    public function __construct(MessageDispatcher $messageBus)
    {
        $this->messageBus = $messageBus;
    }

    /**
     * @return AbstractSyncMessage[]
     */
    abstract public function createMessages(SalesChannelEntity $salesChannel, Context $context, string $runId): array;
}
