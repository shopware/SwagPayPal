<?php declare(strict_types=1);
/*
 * (c) shopware AG <info@shopware.com>
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Swag\PayPal\Test\Pos\MessageQueue\Handler;

use PHPUnit\Framework\TestCase;
use Shopware\Core\Framework\Context;
use Shopware\Core\Framework\DataAbstractionLayer\EntityRepository;
use Shopware\Core\Framework\DataAbstractionLayer\Search\Criteria;
use Shopware\Core\Framework\DataAbstractionLayer\Search\EntitySearchResult;
use Shopware\Core\Framework\Log\Package;
use Shopware\Core\Framework\Test\TestCaseBase\KernelTestBehaviour;
use Shopware\Core\Framework\Uuid\Uuid;
use Shopware\Core\System\SalesChannel\SalesChannelCollection;
use Shopware\Core\System\SalesChannel\SalesChannelEntity;
use Swag\PayPal\Pos\MessageQueue\Handler\InventoryUpdateHandler;
use Swag\PayPal\Pos\MessageQueue\Manager\InventorySyncManager;
use Swag\PayPal\Pos\MessageQueue\Message\InventoryUpdateMessage;
use Swag\PayPal\Pos\MessageQueue\MessageDispatcher;
use Swag\PayPal\Pos\Run\RunService;
use Swag\PayPal\Test\Pos\Helper\SalesChannelTrait;

/**
 * @internal
 */
#[Package('checkout')]
class InventoryUpdateHandlerTest extends TestCase
{
    use KernelTestBehaviour;
    use SalesChannelTrait;

    public function testTest(): void
    {
        $salesChannel = new SalesChannelEntity();
        $salesChannel->setId(Uuid::randomHex());

        $repositoryMock = $this->createMock(EntityRepository::class);
        $repositoryMock
            ->method('search')
            ->willReturn(new EntitySearchResult(
                'sales_channel',
                1,
                new SalesChannelCollection([$salesChannel]),
                null,
                new Criteria(),
                Context::createDefaultContext()
            ));

        $mockResult = [
            'random',
            'AbstractSyncMessage',
            'values',
        ];

        $inventorySyncMock = $this->createMock(InventorySyncManager::class);
        $inventorySyncMock
            ->method('createMessages')
            ->willReturn($mockResult);

        $dispatcherMock = $this->createMock(MessageDispatcher::class);
        $dispatcherMock
            ->method('bulkDispatch')
            ->with($mockResult);

        (new InventoryUpdateHandler(
            $this->createMock(RunService::class),
            $repositoryMock,
            $inventorySyncMock,
            $dispatcherMock,
        ))(new InventoryUpdateMessage());
    }
}
