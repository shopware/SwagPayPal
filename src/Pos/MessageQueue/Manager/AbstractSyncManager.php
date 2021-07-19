<?php declare(strict_types=1);
/*
 * (c) shopware AG <info@shopware.com>
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Swag\PayPal\Pos\MessageQueue\Manager;

use Shopware\Core\Framework\Context;
use Shopware\Core\System\SalesChannel\SalesChannelEntity;
use Symfony\Component\Messenger\MessageBusInterface;

abstract class AbstractSyncManager
{
    /**
     * @deprecated tag:v4.0.0 - will be strongly typed
     *
     * @var MessageBusInterface
     */
    protected $messageBus;

    public function __construct(MessageBusInterface $messageBus)
    {
        $this->messageBus = $messageBus;
    }

    /**
     * @deprecated tag:v4.0.0 - will be removed, use and/or implement createMessages instead
     */
    public function buildMessages(SalesChannelEntity $salesChannel, Context $context, string $runId): void
    {
        $this->createMessages($salesChannel, $context, $runId);
    }

    abstract public function createMessages(SalesChannelEntity $salesChannel, Context $context, string $runId): int;
}
