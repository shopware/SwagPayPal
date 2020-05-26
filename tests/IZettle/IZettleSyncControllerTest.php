<?php declare(strict_types=1);
/*
 * (c) shopware AG <info@shopware.com>
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Swag\PayPal\Test\IZettle;

use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Shopware\Core\Framework\Api\Exception\InvalidSalesChannelIdException;
use Shopware\Core\Framework\Context;
use Swag\PayPal\IZettle\IZettleSyncController;
use Swag\PayPal\IZettle\Run\LogCleaner;
use Swag\PayPal\IZettle\Run\RunService;
use Swag\PayPal\IZettle\Sync\InventorySyncer;
use Swag\PayPal\IZettle\Sync\ProductSyncer;
use Swag\PayPal\Test\Mock\IZettle\SalesChannelRepoMock;

class IZettleSyncControllerTest extends TestCase
{
    private const INVALID_CHANNEL_ID = 'notASalesChannelId';

    /**
     * @var IZettleSyncController
     */
    private $iZettleSyncController;

    /**
     * @var SalesChannelRepoMock
     */
    private $salesChannelRepoMock;

    /**
     * @var MockObject|ProductSyncer
     */
    private $productSyncer;

    /**
     * @var MockObject|InventorySyncer
     */
    private $inventorySyncer;

    /**
     * @var MockObject|LogCleaner
     */
    private $logCleaner;

    protected function setUp(): void
    {
        $this->salesChannelRepoMock = new SalesChannelRepoMock();
        $this->productSyncer = $this->createPartialMock(ProductSyncer::class, ['syncProducts']);
        $this->inventorySyncer = $this->createPartialMock(InventorySyncer::class, ['syncInventory']);
        $this->logCleaner = $this->createPartialMock(LogCleaner::class, ['cleanUpLog']);
        $this->iZettleSyncController = new IZettleSyncController(
            $this->productSyncer,
            $this->inventorySyncer,
            $this->salesChannelRepoMock,
            $this->createMock(RunService::class),
            $this->logCleaner
        );
    }

    public function dataProviderSyncFunctions(): array
    {
        return [
            [
                'syncAll',
                [
                    'productSyncer' => 'syncProducts',
                    'inventorySyncer' => 'syncInventory',
                ],
            ],
            [
                'syncInventory',
                [
                    'inventorySyncer' => 'syncInventory',
                ],
            ],
            [
                'syncProducts',
                [
                    'productSyncer' => 'syncProducts',
                ],
            ],
            [
                'cleanUpLog',
                [
                    'logCleaner' => 'cleanUpLog',
                ],
            ],
        ];
    }

    /**
     * @dataProvider dataProviderSyncFunctions
     */
    public function testSyncWithInvalidId(string $syncFunction): void
    {
        $context = Context::createDefaultContext();
        $this->expectException(InvalidSalesChannelIdException::class);
        $this->iZettleSyncController->$syncFunction(self::INVALID_CHANNEL_ID, $context);
    }

    /**
     * @dataProvider dataProviderSyncFunctions
     */
    public function testSyncNormal(string $syncFunction, array $serviceCalls): void
    {
        $context = Context::createDefaultContext();
        foreach ($serviceCalls as $serviceName => $serviceCall) {
            $this->$serviceName->expects(static::once())->method($serviceCall);
        }
        $salesChannelId = $this->salesChannelRepoMock->getMockEntity();
        $this->iZettleSyncController->$syncFunction($salesChannelId->getId(), $context);
    }
}
