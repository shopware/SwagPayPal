<?php declare(strict_types=1);
/*
 * (c) shopware AG <info@shopware.com>
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace SwagPayPalTestIZettleUtil;

use Monolog\Logger;
use PHPUnit\Framework\TestCase;
use Psr\Log\NullLogger;
use Shopware\Core\Content\Product\DataAbstractionLayer\StockUpdater;
use Shopware\Core\Defaults;
use Shopware\Core\Framework\Context;
use Shopware\Core\Framework\Test\TestCaseBase\KernelTestBehaviour;
use Shopware\Core\System\SystemConfig\SystemConfigService;
use Swag\PayPal\IZettle\Api\Service\ApiKeyDecoder;
use Swag\PayPal\IZettle\Api\Service\Converter\UuidConverter;
use Swag\PayPal\IZettle\Resource\InventoryResource;
use Swag\PayPal\IZettle\Resource\SubscriptionResource;
use Swag\PayPal\IZettle\Run\RunService;
use Swag\PayPal\IZettle\Sync\Context\InventoryContextFactory;
use Swag\PayPal\IZettle\Sync\Inventory\Calculator\LocalWebhookCalculator;
use Swag\PayPal\IZettle\Sync\Inventory\LocalUpdater;
use Swag\PayPal\IZettle\Sync\Inventory\RemoteUpdater;
use Swag\PayPal\IZettle\Sync\InventorySyncer;
use Swag\PayPal\IZettle\Webhook\Handler\InventoryChangedHandler;
use Swag\PayPal\IZettle\Webhook\WebhookController;
use Swag\PayPal\IZettle\Webhook\WebhookRegistry;
use Swag\PayPal\IZettle\Webhook\WebhookService;
use Swag\PayPal\Test\IZettle\ConstantsForTesting;
use Swag\PayPal\Test\IZettle\Helper\SalesChannelTrait;
use Swag\PayPal\Test\IZettle\Mock\Client\IZettleClientFactoryMock;
use Swag\PayPal\Test\IZettle\Mock\Repositories\IZettleInventoryRepoMock;
use Swag\PayPal\Test\IZettle\Mock\Repositories\ProductRepoMock;
use Swag\PayPal\Test\IZettle\Mock\Repositories\RunLogRepoMock;
use Swag\PayPal\Test\IZettle\Mock\Repositories\RunRepoMock;
use Swag\PayPal\Test\IZettle\Mock\Repositories\SalesChannelRepoMock;
use Swag\PayPal\Test\IZettle\Webhook\_fixtures\InventoryChangeFixture;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Router;

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

        $inventoryRepository = new IZettleInventoryRepoMock();
        $inventoryA = $inventoryRepository->createMockEntity($productA, Defaults::SALES_CHANNEL, 3);
        $inventoryVariantA = $inventoryRepository->createMockEntity($variantA, Defaults::SALES_CHANNEL, 15);
        $inventoryVariantB = $inventoryRepository->createMockEntity($variantB, Defaults::SALES_CHANNEL, 11);

        $localCalculator = new LocalWebhookCalculator();
        $inventoryContextFactory = new InventoryContextFactory(
            new InventoryResource(new IZettleClientFactoryMock()),
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
            new RunService(
                new RunRepoMock(),
                new RunLogRepoMock(),
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

        /** @var SystemConfigService $systemConfigService */
        $systemConfigService = $this->getContainer()->get(SystemConfigService::class);
        /** @var Router $router */
        $router = $this->getContainer()->get('router');

        $webhookService = new WebhookService(
            new SubscriptionResource(new IZettleClientFactoryMock()),
            $webhookRegistry,
            $salesChannelRepository,
            $systemConfigService,
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

        $webhookController->executeWebhook(Defaults::SALES_CHANNEL, $request, $context);

        static::assertSame(2, $inventoryA->getStock());
        static::assertSame(4, $productA->getStock());
        static::assertSame(13, $inventoryVariantA->getStock());
        static::assertSame(13, $variantA->getStock());
        static::assertSame(8, $inventoryVariantB->getStock());
        static::assertSame(7, $variantB->getStock());
    }
}
