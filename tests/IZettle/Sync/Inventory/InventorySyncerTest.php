<?php declare(strict_types=1);
/*
 * (c) shopware AG <info@shopware.com>
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Swag\PayPal\Test\IZettle\Sync\Inventory;

use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Shopware\Core\Content\Product\ProductCollection;
use Shopware\Core\Content\ProductStream\Service\ProductStreamBuilderInterface;
use Shopware\Core\Defaults;
use Shopware\Core\Framework\Context;
use Shopware\Core\Framework\DataAbstractionLayer\EntityRepositoryInterface;
use Shopware\Core\Framework\DataAbstractionLayer\Search\Criteria;
use Shopware\Core\Framework\DataAbstractionLayer\Search\EntitySearchResult;
use Shopware\Core\Framework\DataAbstractionLayer\Search\Filter\EqualsFilter;
use Shopware\Core\Framework\DataAbstractionLayer\Search\Filter\NotFilter;
use Shopware\Core\Framework\Test\TestCaseBase\KernelTestBehaviour;
use Shopware\Core\Framework\Uuid\Uuid;
use Shopware\Core\System\SalesChannel\Aggregate\SalesChannelDomain\SalesChannelDomainCollection;
use Shopware\Core\System\SalesChannel\Aggregate\SalesChannelDomain\SalesChannelDomainEntity;
use Shopware\Core\System\SalesChannel\Context\SalesChannelContextFactory;
use Swag\PayPal\IZettle\DataAbstractionLayer\Entity\IZettleSalesChannelEntity;
use Swag\PayPal\IZettle\Resource\InventoryResource;
use Swag\PayPal\IZettle\Sync\Context\InventoryContextFactory;
use Swag\PayPal\IZettle\Sync\Inventory\LocalUpdater;
use Swag\PayPal\IZettle\Sync\Inventory\RemoteUpdater;
use Swag\PayPal\IZettle\Sync\InventorySyncer;
use Swag\PayPal\IZettle\Sync\ProductSelection;
use Swag\PayPal\Test\Mock\IZettle\IZettleInventoryRepoMock;
use Swag\PayPal\Test\Mock\IZettle\SalesChannelProductRepoMock;

class InventorySyncerTest extends TestCase
{
    use KernelTestBehaviour;
    use InventoryTrait;

    /**
     * @var InventorySyncer
     */
    private $inventorySyncer;

    /**
     * @var SalesChannelProductRepoMock
     */
    private $salesChannelProductRepository;

    /**
     * @var IZettleSalesChannelEntity
     */
    private $salesChannel;

    /**
     * @var MockObject|InventoryResource
     */
    private $inventoryResource;

    /**
     * @var MockObject
     */
    private $inventoryRepository;

    /**
     * @var MockObject
     */
    private $localUpdater;

    /**
     * @var MockObject
     */
    private $remoteUpdater;

    public function setUp(): void
    {
        $context = Context::createDefaultContext();

        $this->salesChannel = $this->getIZettleSalesChannel();

        $productStreamBuilder = $this->createStub(ProductStreamBuilderInterface::class);
        $productStreamBuilder->method('buildFilters')->willReturn(
            [new NotFilter(NotFilter::CONNECTION_AND, [
                new EqualsFilter('id', null),
            ])]
        );

        $domain = new SalesChannelDomainEntity();
        $domain->setId(Uuid::randomHex());
        $domain->setSalesChannelId(Defaults::SALES_CHANNEL);
        $domainRepository = $this->createStub(EntityRepositoryInterface::class);
        $domainRepository->method('search')->willReturn(
            new EntitySearchResult(
                1,
                new SalesChannelDomainCollection([$domain]),
                null,
                new Criteria(),
                $context
            )
        );

        $this->salesChannelProductRepository = new SalesChannelProductRepoMock();
        $this->inventoryRepository = $this->createPartialMock(IZettleInventoryRepoMock::class, ['upsert']);
        $this->localUpdater = $this->createMock(LocalUpdater::class);
        $this->remoteUpdater = $this->createMock(RemoteUpdater::class);

        $productSelection = new ProductSelection(
            $this->salesChannelProductRepository,
            $productStreamBuilder,
            $domainRepository,
            $this->createMock(SalesChannelContextFactory::class)
        );

        $this->inventorySyncer = new InventorySyncer(
            $productSelection,
            $this->createStub(InventoryContextFactory::class),
            $this->localUpdater,
            $this->remoteUpdater,
            $this->inventoryRepository
        );
    }

    public function testInventorySyncLocal(): void
    {
        $context = Context::createDefaultContext();

        $product = $this->getSingleProduct();
        $this->salesChannelProductRepository->addMockEntity($product);
        $this->localUpdater->method('updateLocal')->willReturn(new ProductCollection([$product]));
        $this->remoteUpdater->method('updateRemote')->willReturn(new ProductCollection());

        $this->inventoryRepository->expects(static::once())->method('upsert');

        $this->inventorySyncer->syncInventory($this->salesChannel, $context);
    }

    public function testInventorySyncRemote(): void
    {
        $context = Context::createDefaultContext();

        $product = $this->getSingleProduct();
        $this->salesChannelProductRepository->addMockEntity($product);
        $this->localUpdater->method('updateLocal')->willReturn(new ProductCollection());
        $this->remoteUpdater->method('updateRemote')->willReturn(new ProductCollection([$product]));

        $this->inventoryRepository->expects(static::once())->method('upsert');

        $this->inventorySyncer->syncInventory($this->salesChannel, $context);
    }

    public function testInventorySyncBoth(): void
    {
        $context = Context::createDefaultContext();

        $product = $this->getSingleProduct();
        $this->salesChannelProductRepository->addMockEntity($product);
        $this->localUpdater->method('updateLocal')->willReturn(new ProductCollection([$product]));
        $this->remoteUpdater->method('updateRemote')->willReturn(new ProductCollection([$product]));

        $this->inventoryRepository->expects(static::exactly(2))->method('upsert');

        $this->inventorySyncer->syncInventory($this->salesChannel, $context);
    }

    public function testInventorySyncNone(): void
    {
        $context = Context::createDefaultContext();

        $product = $this->getSingleProduct();
        $this->salesChannelProductRepository->addMockEntity($product);
        $this->localUpdater->method('updateLocal')->willReturn(new ProductCollection());
        $this->remoteUpdater->method('updateRemote')->willReturn(new ProductCollection());

        $this->inventoryRepository->expects(static::never())->method('upsert');

        $this->inventorySyncer->syncInventory($this->salesChannel, $context);
    }
}
