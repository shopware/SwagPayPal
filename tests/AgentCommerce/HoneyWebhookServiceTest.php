<?php declare(strict_types=1);
/*
 * (c) shopware AG <info@shopware.com>
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Swag\PayPal\Test\AgentCommerce;

use GuzzleHttp\Client;
use GuzzleHttp\Exception\ClientException;
use GuzzleHttp\Exception\RequestException;
use GuzzleHttp\Psr7\Request;
use GuzzleHttp\Psr7\Response;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;
use Psr\Log\LoggerInterface;
use Shopware\Core\Content\ProductExport\ProductExportCollection;
use Shopware\Core\Content\ProductExport\ProductExportEntity;
use Shopware\Core\Framework\Context;
use Shopware\Core\Framework\DataAbstractionLayer\EntityRepository;
use Shopware\Core\Framework\DataAbstractionLayer\Search\Criteria;
use Shopware\Core\Framework\DataAbstractionLayer\Search\EntitySearchResult;
use Shopware\Core\Framework\JWT\JWTDecoder;
use Shopware\Core\Framework\Log\Package;
use Shopware\Core\Framework\Uuid\Uuid;
use Shopware\Core\System\Country\CountryCollection;
use Shopware\Core\System\Country\CountryEntity;
use Shopware\Core\System\Currency\CurrencyEntity;
use Shopware\Core\System\SalesChannel\Aggregate\SalesChannelDomain\SalesChannelDomainCollection;
use Shopware\Core\System\SalesChannel\Aggregate\SalesChannelDomain\SalesChannelDomainEntity;
use Shopware\Core\System\SalesChannel\SalesChannelCollection;
use Shopware\Core\System\SalesChannel\SalesChannelEntity;
use Shopware\Core\System\SystemConfig\SystemConfigService;
use Swag\PayPal\AgentCommerce\Exception\HoneyWebhookExceptions;
use Swag\PayPal\AgentCommerce\HoneyWebhookService;
use Swag\PayPal\Setting\Service\CredentialsUtil;
use Swag\PayPal\Setting\Settings;
use Swag\PayPal\SwagPayPal;
use Symfony\Component\Routing\Route;
use Symfony\Component\Routing\RouteCollection;
use Symfony\Component\Routing\RouterInterface;

/**
 * @internal
 */
#[Package('checkout')]
#[CoversClass(HoneyWebhookService::class)]
class HoneyWebhookServiceTest extends TestCase
{
    public function testRegisterWebhook(): void
    {
        $context = Context::createCLIContext();
        $salesChannel = self::createSalesChannel();
        $salesChannelRepository = $this->createMock(EntityRepository::class);
        $salesChannelRepository
            ->expects($this->once())
            ->method('search')
            ->willReturn(new EntitySearchResult('sales_channel', 1, new SalesChannelCollection([$salesChannel]), null, new Criteria(), $context));

        $client = $this->createMock(Client::class);
        $client
            ->expects($this->once())
            ->method('request')
            ->with('POST', 'webhooks/sw/install')
            ->willReturnCallback(function (string $method, string $url, array $options) use ($salesChannel) {
                $jwt = (new JWTDecoder())->decode($options['body']);

                static::assertSame('SalesChannel name', $jwt['storeName']);
                static::assertSame('https://example.com/', $jwt['storeUrl']);
                static::assertSame('DE', $jwt['country']);
                static::assertSame('EUR', $jwt['currency']);
                static::assertEmpty(array_diff(['DE', 'UK'], $jwt['shippingCountries']));
                static::assertSame('SomeMerchantId', $jwt['paypalMerchantId']);
                static::assertSame($salesChannel->getId(), $jwt['shopwareMerchantId']);
                static::assertSame('https://example.com/test/path/export', $jwt['catalogDownloadUrl']);

                return new Response(body: (string) json_encode(['message' => 'Merchant onboarded successfully']));
            });

        $credentialsUtil = $this->createMock(CredentialsUtil::class);
        $credentialsUtil
            ->expects($this->once())
            ->method('getMerchantPayerId')
            ->willReturn('SomeMerchantId');

        $route = new Route('/test/path/export');
        $routeCollection = new RouteCollection();
        $routeCollection->add('store-api.product.export', $route);

        $routeMock = $this->createMock(RouterInterface::class);
        $routeMock
            ->expects($this->once())
            ->method('getRouteCollection')
            ->willReturn($routeCollection);

        $configServiceMock = $this->createMock(SystemConfigService::class);
        $configServiceMock
            ->expects($this->once())
            ->method('getBool')
            ->with(Settings::AGENT_COMMERCE_ONBOARDED)
            ->willReturn(false);
        $configServiceMock
            ->expects($this->once())
            ->method('set')
            ->with(Settings::AGENT_COMMERCE_ONBOARDED, true);

        $loggerMock = $this->createMock(LoggerInterface::class);
        $loggerMock
            ->expects($this->once())
            ->method('info')
            ->with('PayPal agent commerce webhook install', [
                'salesChannelId' => $salesChannel->getId(),
                'success' => true,
                'message' => 'Merchant onboarded successfully',
                'error' => null,
            ]);

        $service = HoneyWebhookServiceMock::create(
            $salesChannelRepository,
            $credentialsUtil,
            $routeMock,
            $configServiceMock,
            $loggerMock,
            $client
        );

        $response = $service->register($salesChannel->getId(), $context);
        $content = json_decode($response->getBody()->getContents(), true);

        static::assertIsArray($content);
        static::assertSame(['message' => 'Merchant onboarded successfully'], $content);
    }

    public function testDeregisterWebhook(): void
    {
        $context = Context::createCLIContext();
        $salesChannel = self::createSalesChannel();
        $salesChannelRepository = $this->createMock(EntityRepository::class);
        $salesChannelRepository
            ->expects($this->once())
            ->method('search')
            ->willReturn(new EntitySearchResult('sales_channel', 1, new SalesChannelCollection([$salesChannel]), null, new Criteria(), $context));

        $client = $this->createMock(Client::class);
        $client
            ->expects($this->once())
            ->method('request')
            ->with('POST', 'webhooks/sw/uninstall')
            ->willReturnCallback(function (string $method, string $url, array $options) use ($salesChannel) {
                $jwt = (new JWTDecoder())->decode($options['body']);

                static::assertSame('SalesChannel name', $jwt['storeName']);
                static::assertSame('https://example.com/', $jwt['storeUrl']);
                static::assertSame('DE', $jwt['country']);
                static::assertSame('EUR', $jwt['currency']);
                static::assertEmpty(array_diff(['DE', 'UK'], $jwt['shippingCountries']));
                static::assertSame('SomeMerchantId', $jwt['paypalMerchantId']);
                static::assertSame($salesChannel->getId(), $jwt['shopwareMerchantId']);
                static::assertSame('https://example.com/test/path/export', $jwt['catalogDownloadUrl']);

                return new Response(body: (string) json_encode(['message' => 'Merchant onboarded successfully']));
            });

        $credentialsUtil = $this->createMock(CredentialsUtil::class);
        $credentialsUtil
            ->expects($this->once())
            ->method('getMerchantPayerId')
            ->willReturn('SomeMerchantId');

        $route = new Route('/test/path/export');
        $routeCollection = new RouteCollection();
        $routeCollection->add('store-api.product.export', $route);

        $routeMock = $this->createMock(RouterInterface::class);
        $routeMock
            ->expects($this->once())
            ->method('getRouteCollection')
            ->willReturn($routeCollection);

        $configServiceMock = $this->createMock(SystemConfigService::class);
        $configServiceMock
            ->expects($this->once())
            ->method('getBool')
            ->with(Settings::AGENT_COMMERCE_ONBOARDED)
            ->willReturn(true);
        $configServiceMock
            ->expects($this->once())
            ->method('set')
            ->with(Settings::AGENT_COMMERCE_ONBOARDED, false);

        $loggerMock = $this->createMock(LoggerInterface::class);
        $loggerMock
            ->expects($this->once())
            ->method('info')
            ->with('PayPal agent commerce webhook uninstall', [
                'salesChannelId' => $salesChannel->getId(),
                'success' => true,
                'message' => 'Merchant onboarded successfully',
                'error' => null,
            ]);

        $service = HoneyWebhookServiceMock::create(
            $salesChannelRepository,
            $credentialsUtil,
            $routeMock,
            $configServiceMock,
            $loggerMock,
            $client
        );

        $response = $service->deregister($salesChannel->getId(), $context);
        $content = json_decode($response->getBody()->getContents(), true);

        static::assertIsArray($content);
        static::assertSame(['message' => 'Merchant onboarded successfully'], $content);
    }

    public function testReRegister(): void
    {
        $context = Context::createCLIContext();
        $salesChannel = self::createSalesChannel();
        $salesChannelRepository = $this->createMock(EntityRepository::class);
        $salesChannelRepository
            ->expects($this->once())
            ->method('search')
            ->willReturn(new EntitySearchResult('sales_channel', 1, new SalesChannelCollection([$salesChannel]), null, new Criteria(), $context));

        $client = $this->createMock(Client::class);
        $client
            ->expects($this->exactly(2))
            ->method('request')
            ->willReturnCallback(function (string $method, string $url, array $options) use ($salesChannel) {
                $jwt = (new JWTDecoder())->decode($options['body']);

                static::assertSame('SalesChannel name', $jwt['storeName']);
                static::assertSame('https://example.com/', $jwt['storeUrl']);
                static::assertSame('DE', $jwt['country']);
                static::assertSame('EUR', $jwt['currency']);
                static::assertEmpty(array_diff(['DE', 'UK'], $jwt['shippingCountries']));
                static::assertSame('SomeMerchantId', $jwt['paypalMerchantId']);
                static::assertSame($salesChannel->getId(), $jwt['shopwareMerchantId']);
                static::assertSame('https://example.com/test/path/export', $jwt['catalogDownloadUrl']);

                return new Response(body: (string) json_encode(['message' => 'Merchant onboarded successfully']));
            });

        $credentialsUtil = $this->createMock(CredentialsUtil::class);
        $credentialsUtil
            ->method('getMerchantPayerId')
            ->willReturn('SomeMerchantId');

        $route = new Route('/test/path/export');
        $routeCollection = new RouteCollection();
        $routeCollection->add('store-api.product.export', $route);

        $routeMock = $this->createMock(RouterInterface::class);
        $routeMock
            ->method('getRouteCollection')
            ->willReturn($routeCollection);

        $configServiceMock = $this->createMock(SystemConfigService::class);
        $configServiceMock
            ->method('getBool')
            ->with(Settings::AGENT_COMMERCE_ONBOARDED)
            ->willReturn(true);
        $configServiceMock
            ->expects($this->once())
            ->method('set')
            ->with(Settings::AGENT_COMMERCE_ONBOARDED, true);

        $loggerMock = $this->createMock(LoggerInterface::class);
        $loggerMock
            ->expects($this->exactly(2))
            ->method('info');

        $service = HoneyWebhookServiceMock::create(
            $salesChannelRepository,
            $credentialsUtil,
            $routeMock,
            $configServiceMock,
            $loggerMock,
            $client
        );

        $response = $service->register($salesChannel->getId(), $context);
        $content = json_decode($response->getBody()->getContents(), true);

        static::assertIsArray($content);
        static::assertSame(['message' => 'Merchant onboarded successfully'], $content);
    }

    public function testFailedReRegister(): void
    {
        $this->expectException(HoneyWebhookExceptions::class);
        $this->expectExceptionMessage('Failed deregister the webhook');

        $context = Context::createCLIContext();
        $salesChannel = self::createSalesChannel();
        $salesChannelRepository = $this->createMock(EntityRepository::class);
        $salesChannelRepository
            ->expects($this->once())
            ->method('search')
            ->willReturn(new EntitySearchResult('sales_channel', 1, new SalesChannelCollection([$salesChannel]), null, new Criteria(), $context));

        $client = $this->createMock(Client::class);
        $client
            ->expects($this->once())
            ->method('request')
            ->willReturnCallback(function (): void {
                $response = new Response(400, body: (string) json_encode([
                    'success' => false,
                    'error' => 'INVALID_JWT',
                    'message' => 'JWT signature verification failed',
                ]));

                throw new ClientException('Something went wrong', new Request('POST', 'webhooks/sw/uninstall'), $response);
            });

        $route = new Route('/test/path/export');
        $routeCollection = new RouteCollection();
        $routeCollection->add('store-api.product.export', $route);

        $routeMock = $this->createMock(RouterInterface::class);
        $routeMock
            ->method('getRouteCollection')
            ->willReturn($routeCollection);

        $configServiceMock = $this->createMock(SystemConfigService::class);
        $configServiceMock
            ->method('getBool')
            ->with(Settings::AGENT_COMMERCE_ONBOARDED)
            ->willReturn(true);
        $configServiceMock
            ->expects($this->never())
            ->method('set')
            ->with(Settings::AGENT_COMMERCE_ONBOARDED, true);

        $loggerMock = $this->createMock(LoggerInterface::class);
        $loggerMock
            ->expects($this->once())
            ->method('error');

        $service = HoneyWebhookServiceMock::create(
            $salesChannelRepository,
            $this->createMock(CredentialsUtil::class),
            $routeMock,
            $configServiceMock,
            $loggerMock,
            $client
        );

        $service->register($salesChannel->getId(), $context);
    }

    public function testDeregisterNotRegistered(): void
    {
        $this->expectException(HoneyWebhookExceptions::class);
        $this->expectExceptionMessage('Sales channel is not registered and can\'t be deregistered');

        $client = $this->createMock(Client::class);
        $client
            ->expects($this->never())
            ->method('request');

        $configServiceMock = $this->createMock(SystemConfigService::class);
        $configServiceMock
            ->expects($this->once())
            ->method('getBool')
            ->with(Settings::AGENT_COMMERCE_ONBOARDED)
            ->willReturn(false);
        $configServiceMock
            ->expects($this->never())
            ->method('set')
            ->with(Settings::AGENT_COMMERCE_ONBOARDED, false);

        $service = HoneyWebhookServiceMock::create(
            $this->createMock(EntityRepository::class),
            $this->createMock(CredentialsUtil::class),
            $this->createMock(RouterInterface::class),
            $configServiceMock,
            $this->createMock(LoggerInterface::class),
            $client
        );

        $service->deregister(Uuid::randomHex(), Context::createCLIContext());
    }

    #[DataProvider('dataProviderMissingSalesChannelDataRegister')]
    public function testMissingSalesChannelDataRegister(?SalesChannelEntity $salesChannel, string $exceptionMessage): void
    {
        $this->expectException(HoneyWebhookExceptions::class);
        $this->expectExceptionMessage($exceptionMessage);

        $searchResult = new EntitySearchResult('sales_channel', 0, new SalesChannelCollection(), null, new Criteria(), Context::createCLIContext());
        if ($salesChannel) {
            $searchResult = new EntitySearchResult('sales_channel', 1, new SalesChannelCollection([$salesChannel]), null, new Criteria(), Context::createCLIContext());
        }

        $context = Context::createCLIContext();
        $salesChannel = self::createSalesChannel();
        $salesChannelRepository = $this->createMock(EntityRepository::class);
        $salesChannelRepository
            ->expects($this->once())
            ->method('search')
            ->willReturn($searchResult);

        $routeMock = $this->createMock(RouterInterface::class);
        $routeMock
            ->method('getRouteCollection')
            ->willReturn(new RouteCollection());

        $configServiceMock = $this->createMock(SystemConfigService::class);
        $configServiceMock
            ->method('getBool')
            ->with(Settings::AGENT_COMMERCE_ONBOARDED)
            ->willReturn(false);

        $client = $this->createMock(Client::class);
        $client
            ->expects($this->never())
            ->method('request');

        $service = HoneyWebhookServiceMock::create(
            $salesChannelRepository,
            $this->createMock(CredentialsUtil::class),
            $routeMock,
            $configServiceMock,
            $this->createMock(LoggerInterface::class),
            $client
        );

        $service->register($salesChannel->getId(), $context);
    }

    #[DataProvider('dataProviderMissingSalesChannelDataDeregister')]
    public function testMissingSalesChannelDataDeregister(?SalesChannelEntity $salesChannel, string $exceptionMessage): void
    {
        $this->expectException(HoneyWebhookExceptions::class);
        $this->expectExceptionMessage($exceptionMessage);

        $searchResult = new EntitySearchResult('sales_channel', 0, new SalesChannelCollection(), null, new Criteria(), Context::createCLIContext());
        if ($salesChannel) {
            $searchResult = new EntitySearchResult('sales_channel', 1, new SalesChannelCollection([$salesChannel]), null, new Criteria(), Context::createCLIContext());
        }

        $context = Context::createCLIContext();
        $salesChannel = self::createSalesChannel();
        $salesChannelRepository = $this->createMock(EntityRepository::class);
        $salesChannelRepository
            ->expects($this->once())
            ->method('search')
            ->willReturn($searchResult);

        $routeMock = $this->createMock(RouterInterface::class);
        $routeMock
            ->method('getRouteCollection')
            ->willReturn(new RouteCollection());

        $configServiceMock = $this->createMock(SystemConfigService::class);
        $configServiceMock
            ->method('getBool')
            ->with(Settings::AGENT_COMMERCE_ONBOARDED)
            ->willReturn(true);

        $client = $this->createMock(Client::class);
        $client
            ->expects($this->never())
            ->method('request');

        $service = HoneyWebhookServiceMock::create(
            $salesChannelRepository,
            $this->createMock(CredentialsUtil::class),
            $routeMock,
            $configServiceMock,
            $this->createMock(LoggerInterface::class),
            $client
        );

        $service->deregister($salesChannel->getId(), $context);
    }

    public static function dataProviderMissingSalesChannelDataRegister(): \Generator
    {
        yield 'no sales channel found' => [null, 'Agent commerce sales channel not found'];

        $salesChannel = self::createSalesChannel();
        $salesChannel->setActive(false);
        yield 'no sales channel not active' => [$salesChannel, 'Agent commerce sales channel not found'];

        $salesChannel = self::createSalesChannel();
        $salesChannel->setProductExports(new ProductExportCollection());
        yield 'no export' => [$salesChannel, 'Product export sales channel not found'];

        $productExport = new ProductExportEntity();
        $productExport->setId(Uuid::randomHex());

        $salesChannel = self::createSalesChannel();
        $salesChannel->setProductExports(new ProductExportCollection([$productExport]));
        yield 'no storefront sales channel' => [$salesChannel, 'Storefront sales channel not found'];

        $salesChannel = self::createSalesChannel();
        yield 'no route' => [$salesChannel, 'Invalid product export route'];
    }

    public static function dataProviderMissingSalesChannelDataDeregister(): \Generator
    {
        $testCases = static::dataProviderMissingSalesChannelDataRegister();
        foreach ($testCases as $key => $testCase) {
            // Deregister don't check for the active flag
            if ($key === 'no sales channel not active') {
                continue;
            }

            yield $key => $testCase;
        }
    }

    public function testInvalidRegisterRequest(): void
    {
        $context = Context::createCLIContext();
        $salesChannel = self::createSalesChannel();
        $salesChannelRepository = $this->createMock(EntityRepository::class);
        $salesChannelRepository
            ->expects($this->once())
            ->method('search')
            ->willReturn(new EntitySearchResult('sales_channel', 1, new SalesChannelCollection([$salesChannel]), null, new Criteria(), $context));

        $client = $this->createMock(Client::class);
        $client
            ->expects($this->once())
            ->method('request')
            ->with('POST', 'webhooks/sw/install')
            ->willReturnCallback(function (): void {
                $response = new Response(400, body: (string) json_encode([
                    'success' => false,
                    'error' => 'INVALID_JWT',
                    'message' => 'JWT signature verification failed',
                ]));

                throw new ClientException('Something went wrong', new Request('POST', 'webhooks/sw/install'), $response);
            });

        $route = new Route('/test/path/export');
        $routeCollection = new RouteCollection();
        $routeCollection->add('store-api.product.export', $route);

        $routeMock = $this->createMock(RouterInterface::class);
        $routeMock
            ->expects($this->once())
            ->method('getRouteCollection')
            ->willReturn($routeCollection);

        $configServiceMock = $this->createMock(SystemConfigService::class);
        $configServiceMock
            ->expects($this->once())
            ->method('getBool')
            ->with(Settings::AGENT_COMMERCE_ONBOARDED)
            ->willReturn(false);
        $configServiceMock
            ->expects($this->once())
            ->method('set')
            ->with(Settings::AGENT_COMMERCE_ONBOARDED, false);

        $loggerMock = $this->createMock(LoggerInterface::class);
        $loggerMock
            ->expects($this->once())
            ->method('error')
            ->with('PayPal agent commerce webhook install', [
                'salesChannelId' => $salesChannel->getId(),
                'success' => false,
                'message' => 'JWT signature verification failed',
                'error' => 'INVALID_JWT',
            ]);

        $service = HoneyWebhookServiceMock::create(
            $salesChannelRepository,
            $this->createMock(CredentialsUtil::class),
            $routeMock,
            $configServiceMock,
            $loggerMock,
            $client
        );

        $response = $service->register($salesChannel->getId(), $context);
        $content = json_decode($response->getBody()->getContents(), true);

        $expectedMessage = [
            'success' => false,
            'error' => 'INVALID_JWT',
            'message' => 'JWT signature verification failed',
        ];

        static::assertIsArray($content);
        static::assertSame($expectedMessage, $content);
    }

    public function testInvalidDeregisterRequest(): void
    {
        $context = Context::createCLIContext();
        $salesChannel = self::createSalesChannel();
        $salesChannelRepository = $this->createMock(EntityRepository::class);
        $salesChannelRepository
            ->expects($this->once())
            ->method('search')
            ->willReturn(new EntitySearchResult('sales_channel', 1, new SalesChannelCollection([$salesChannel]), null, new Criteria(), $context));

        $client = $this->createMock(Client::class);
        $client
            ->expects($this->once())
            ->method('request')
            ->with('POST', 'webhooks/sw/uninstall')
            ->willReturnCallback(function (): void {
                $response = new Response(400, body: (string) json_encode([
                    'success' => false,
                    'error' => 'INVALID_JWT',
                    'message' => 'JWT signature verification failed',
                ]));

                throw new ClientException('Something went wrong', new Request('POST', 'webhooks/sw/uninstall'), $response);
            });

        $route = new Route('/test/path/export');
        $routeCollection = new RouteCollection();
        $routeCollection->add('store-api.product.export', $route);

        $routeMock = $this->createMock(RouterInterface::class);
        $routeMock
            ->expects($this->once())
            ->method('getRouteCollection')
            ->willReturn($routeCollection);

        $configServiceMock = $this->createMock(SystemConfigService::class);
        $configServiceMock
            ->expects($this->once())
            ->method('getBool')
            ->with(Settings::AGENT_COMMERCE_ONBOARDED)
            ->willReturn(true);
        $configServiceMock
            ->expects($this->never())
            ->method('set');

        $loggerMock = $this->createMock(LoggerInterface::class);
        $loggerMock
            ->expects($this->once())
            ->method('error')
            ->with('PayPal agent commerce webhook uninstall', [
                'salesChannelId' => $salesChannel->getId(),
                'success' => false,
                'message' => 'JWT signature verification failed',
                'error' => 'INVALID_JWT',
            ]);

        $service = HoneyWebhookServiceMock::create(
            $salesChannelRepository,
            $this->createMock(CredentialsUtil::class),
            $routeMock,
            $configServiceMock,
            $loggerMock,
            $client
        );

        $response = $service->deregister($salesChannel->getId(), $context);
        $content = json_decode($response->getBody()->getContents(), true);

        $expectedMessage = [
            'success' => false,
            'error' => 'INVALID_JWT',
            'message' => 'JWT signature verification failed',
        ];

        static::assertIsArray($content);
        static::assertSame($expectedMessage, $content);
    }

    public function testInvalidRequestNoResponse(): void
    {
        $this->expectException(RequestException::class);
        $this->expectExceptionMessage('Something went wrong');

        $context = Context::createCLIContext();
        $salesChannel = self::createSalesChannel();
        $salesChannelRepository = $this->createMock(EntityRepository::class);
        $salesChannelRepository
            ->expects($this->once())
            ->method('search')
            ->willReturn(new EntitySearchResult('sales_channel', 1, new SalesChannelCollection([$salesChannel]), null, new Criteria(), $context));

        $client = $this->createMock(Client::class);
        $client
            ->expects($this->once())
            ->method('request')
            ->with('POST', 'webhooks/sw/install')
            ->willReturnCallback(function (): void {
                throw new RequestException('Something went wrong', new Request('POST', 'webhooks/sw/install'));
            });

        $route = new Route('/test/path/export');
        $routeCollection = new RouteCollection();
        $routeCollection->add('store-api.product.export', $route);

        $routeMock = $this->createMock(RouterInterface::class);
        $routeMock
            ->expects($this->once())
            ->method('getRouteCollection')
            ->willReturn($routeCollection);

        $configServiceMock = $this->createMock(SystemConfigService::class);
        $configServiceMock
            ->expects($this->once())
            ->method('getBool')
            ->with(Settings::AGENT_COMMERCE_ONBOARDED)
            ->willReturn(false);
        $configServiceMock
            ->expects($this->never())
            ->method('set')
            ->with(Settings::AGENT_COMMERCE_ONBOARDED, false);

        $service = HoneyWebhookServiceMock::create(
            $salesChannelRepository,
            $this->createMock(CredentialsUtil::class),
            $routeMock,
            $configServiceMock,
            $this->createMock(LoggerInterface::class),
            $client
        );

        $response = $service->register($salesChannel->getId(), $context);
        $content = json_decode($response->getBody()->getContents(), true);

        $expectedMessage = [
            'success' => false,
            'error' => 'INVALID_JWT',
            'message' => 'JWT signature verification failed',
        ];

        static::assertIsArray($content);
        static::assertSame($expectedMessage, $content);
    }

    private static function createSalesChannel(): SalesChannelEntity
    {
        $de = new CountryEntity();
        $de->setId(Uuid::randomHex());
        $de->setIso('DE');
        $uk = new CountryEntity();
        $uk->setId(Uuid::randomHex());
        $uk->setIso('UK');

        $eur = new CurrencyEntity();
        $eur->setId(Uuid::randomHex());
        $eur->setIsoCode('EUR');

        $domain = new SalesChannelDomainEntity();
        $domain->setId(Uuid::randomHex());
        $domain->setUrl('https://example.com/'); // with "/" to test rtrim

        $storefrontSalesChannel = new SalesChannelEntity();
        $storefrontSalesChannel->setId(Uuid::randomHex());
        $storefrontSalesChannel->setCountries(new CountryCollection([$de, $uk]));
        $storefrontSalesChannel->setDomains(new SalesChannelDomainCollection([$domain]));
        $storefrontSalesChannel->setHreflangDefaultDomain($domain);

        $productExport = new ProductExportEntity();
        $productExport->setId(Uuid::randomHex());
        $productExport->setStorefrontSalesChannelId($storefrontSalesChannel->getId());
        $productExport->setStorefrontSalesChannel($storefrontSalesChannel);
        $productExport->setAccessKey(Uuid::randomHex());
        $productExport->setFileName('test.test');

        $salesChannel = new SalesChannelEntity();
        $salesChannel->setId(Uuid::randomHex());
        $salesChannel->setActive(true);
        $salesChannel->setTypeId(SwagPayPal::SALES_CHANNEL_TYPE_AGENT_COMMERCE);
        $salesChannel->setProductExports(new ProductExportCollection([$productExport]));
        $salesChannel->setCountry($de);
        $salesChannel->setCurrency($eur);
        $salesChannel->setTranslated(['name' => 'SalesChannel name']);

        return $salesChannel;
    }
}
