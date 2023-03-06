<?php declare(strict_types=1);
/*
 * (c) shopware AG <info@shopware.com>
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace SwagPayPalTestPosUtil;

use Doctrine\DBAL\Connection;
use Monolog\Logger;
use PHPUnit\Framework\TestCase;
use Psr\Log\NullLogger;
use Shopware\Core\Content\Product\DataAbstractionLayer\StockUpdater;
use Shopware\Core\Framework\Context;
use Shopware\Core\Framework\Test\TestCaseBase\KernelTestBehaviour;
use Shopware\Core\System\SystemConfig\SystemConfigService;
use Shopware\Core\Test\TestDefaults;
use Swag\PayPal\Pos\Api\Service\ApiKeyDecoder;
use Swag\PayPal\Pos\Api\Service\Converter\UuidConverter;
use Swag\PayPal\Pos\Resource\InventoryResource;
use Swag\PayPal\Pos\Resource\SubscriptionResource;
use Swag\PayPal\Pos\Sync\Context\InventoryContextFactory;
use Swag\PayPal\Pos\Sync\Inventory\Calculator\LocalWebhookCalculator;
use Swag\PayPal\Pos\Sync\Inventory\LocalUpdater;
use Swag\PayPal\Pos\Sync\Inventory\RemoteUpdater;
use Swag\PayPal\Pos\Sync\InventorySyncer;
use Swag\PayPal\Pos\Webhook\Handler\InventoryChangedHandler;
use Swag\PayPal\Pos\Webhook\WebhookController;
use Swag\PayPal\Pos\Webhook\WebhookRegistry;
use Swag\PayPal\Pos\Webhook\WebhookService;
use Swag\PayPal\Test\Pos\ConstantsForTesting;
use Swag\PayPal\Test\Pos\Helper\SalesChannelTrait;
use Swag\PayPal\Test\Pos\Mock\Client\PosClientFactoryMock;
use Swag\PayPal\Test\Pos\Mock\Repositories\PosInventoryRepoMock;
use Swag\PayPal\Test\Pos\Mock\Repositories\ProductRepoMock;
use Swag\PayPal\Test\Pos\Mock\Repositories\RunLogRepoMock;
use Swag\PayPal\Test\Pos\Mock\Repositories\RunRepoMock;
use Swag\PayPal\Test\Pos\Mock\Repositories\SalesChannelRepoMock;
use Swag\PayPal\Test\Pos\Mock\RunServiceMock;
use Swag\PayPal\Test\Pos\Webhook\_fixtures\InventoryChangeFixture;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Router;

/**
 * @internal
 */
class InventoryChangedTest extends TestCase
{
    use SalesChannelTrait;
    use KernelTestBehaviour;

    public function testInventoryChanged(): void
    {
        $context = Context::createDefaultContext();

        $productRepository = new ProductRepoMock();
        $productA = $productRepository->createMockEntity('testA', 5, 3, ConstantsForTesting::PRODUCT_A_ID);
        $variantA = $productRepository->createMockEntity('testVariantA', 15, 15, ConstantsForTesting::VARIANT_A_ID);
        $variantB = $productRepository->createMockEntity('testVariantB', 10, 9, ConstantsForTesting::VARIANT_B_ID);
        $variantA->setParentId(ConstantsForTesting::PRODUCT_F_ID);
        $variantB->setParentId(ConstantsForTesting::PRODUCT_F_ID);

        $inventoryRepository = new PosInventoryRepoMock();
        $inventoryA = $inventoryRepository->createMockEntity($productA, TestDefaults::SALES_CHANNEL, 3);
        $inventoryVariantA = $inventoryRepository->createMockEntity($variantA, TestDefaults::SALES_CHANNEL, 15);
        $inventoryVariantB = $inventoryRepository->createMockEntity($variantB, TestDefaults::SALES_CHANNEL, 11);

        $localCalculator = new LocalWebhookCalculator();
        $inventoryContextFactory = new InventoryContextFactory(
            new InventoryResource(new PosClientFactoryMock()),
            new UuidConverter(),
            $inventoryRepository
        );
        $localUpdater = new LocalUpdater(
            $productRepository,
            $localCalculator,
            $this->createMock(StockUpdater::class),
            new NullLogger()
        );

        $inventoryChangedHandler = new InventoryChangedHandler(
            new ApiKeyDecoder(),
            new RunServiceMock(
                new RunRepoMock(),
                new RunLogRepoMock(),
                $this->createMock(Connection::class),
                new Logger('test')
            ),
            $localCalculator,
            $localUpdater,
            new InventorySyncer(
                $inventoryContextFactory,
                $localUpdater,
                $this->createMock(RemoteUpdater::class),
                $inventoryRepository
            ),
            $inventoryContextFactory,
            $productRepository,
            new UuidConverter()
        );

        $salesChannel = $this->getSalesChannel($context);

        $webhookRegistry = new WebhookRegistry(new \ArrayObject([$inventoryChangedHandler]));
        $salesChannelRepository = new SalesChannelRepoMock();
        $salesChannelRepository->addMockEntity($salesChannel);

        /** @var Router $router */
        $router = $this->getContainer()->get('router');

        $webhookService = new WebhookService(
            new SubscriptionResource(new PosClientFactoryMock()),
            $webhookRegistry,
            $salesChannelRepository,
            $this->getContainer()->get(SystemConfigService::class),
            new UuidConverter(),
            $router
        );

        $webhookController = new WebhookController(
            new NullLogger(),
            $webhookService,
            $salesChannelRepository
        );

        $request = new Request([], InventoryChangeFixture::getWebhookFixture());
        $request->headers->add(['x-izettle-signature' => InventoryChangeFixture::getSignature()]);

        $webhookController->executeWebhook(TestDefaults::SALES_CHANNEL, $request, $context);

        static::assertSame(2, $inventoryA->getStock());
        static::assertSame(4, $productA->getStock());
        static::assertSame(13, $inventoryVariantA->getStock());
        static::assertSame(13, $variantA->getStock());
        static::assertSame(8, $inventoryVariantB->getStock());
        static::assertSame(7, $variantB->getStock());
    }
}
