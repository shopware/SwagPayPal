<?php declare(strict_types=1);
/*
 * (c) shopware AG <info@shopware.com>
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Swag\PayPal\Test\AgenticCommerce;

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
use Swag\PayPal\AgenticCommerce\Exception\HoneyWebhookException;
use Swag\PayPal\AgenticCommerce\HoneyWebhookService;
use Swag\PayPal\AgenticCommerce\Util\FaviconLoader;
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

        $client = $this->createMock(HoneyClientMock::class);
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
                static::assertSame('https://example.com/favicon.ico', $jwt['favIcon']);
                static::assertSame(['DE', 'UK'], $jwt['shippingCountries']);
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
            ->method('get')
            ->with(Settings::AGENTIC_COMMERCE_ONBOARDED)
            ->willReturn(false);
        $configServiceMock
            ->expects($this->once())
            ->method('set')
            ->with(Settings::AGENTIC_COMMERCE_ONBOARDED, true);

        $loggerMock = $this->createMock(LoggerInterface::class);
        $loggerMock
            ->expects($this->once())
            ->method('log')
            ->with('info', 'PayPal agentic commerce webhook install', [
                'success' => true,
                'message' => 'Merchant onboarded successfully',
                'error' => null,
            ]);

        $faviconMock = $this->createMock(FaviconLoader::class);
        $faviconMock
            ->expects($this->once())
            ->method('loadFaviconLink')
            ->willReturn('https://example.com/favicon.ico');

        $service = new HoneyWebhookService(
            $client,
            $salesChannelRepository,
            $credentialsUtil,
            $routeMock,
            $configServiceMock,
            $loggerMock,
            $faviconMock
        );

        $result = $service->register($salesChannel->getId(), $context);

        static::assertTrue($result->success);
        static::assertSame('Merchant onboarded successfully', $result->message);
    }

    public function testDeregisterWebhook(): void
    {
        $client = $this->createMock(HoneyClientMock::class);
        $client
            ->expects($this->once())
            ->method('request')
            ->with('POST', 'webhooks/sw/uninstall')
            ->willReturnCallback(function (string $method, string $url, array $options) {
                $jwt = (new JWTDecoder())->decode($options['body']);

                static::assertSame('SalesChannel name', $jwt['storeName']);
                static::assertSame('https://example.com/', $jwt['storeUrl']);
                static::assertSame('DE', $jwt['country']);
                static::assertSame('EUR', $jwt['currency']);
                static::assertEmpty(array_diff(['DE', 'UK'], $jwt['shippingCountries']));
                static::assertSame('SomeMerchantId', $jwt['paypalMerchantId']);
                static::assertSame('019980f9426c716baa53befcd0879fb4', $jwt['shopwareMerchantId']);
                static::assertSame('https://example.com/test/path/export', $jwt['catalogDownloadUrl']);

                return new Response(body: (string) json_encode(['message' => 'Merchant onboarded successfully']));
            });

        $configServiceMock = $this->createMock(SystemConfigService::class);
        $configServiceMock
            ->expects($this->once())
            ->method('get')
            ->with(Settings::AGENTIC_COMMERCE_ONBOARDED)
            ->willReturn('eyJ0eXAiOiJKV1QiLCJhbGciOiJIUzI1NiJ9.eyJzdG9yZU5hbWUiOiJTYWxlc0NoYW5uZWwgbmFtZSIsInN0b3JlVXJsIjoiaHR0cHM6Ly9leGFtcGxlLmNvbS8iLCJjb3VudHJ5IjoiREUiLCJjdXJyZW5jeSI6IkVVUiIsImZhdkljb24iOiJodHRwczovL2xvY2FsaG9zdC9mYXZpY29uLmljbyIsInNoaXBwaW5nQ291bnRyaWVzIjp7IjAxOTk4MGY5NDI2YzcxNmJhYTUzYmVmY2NjODRjNWM2IjoiREUiLCIwMTk5ODBmOTQyNmM3MTZiYWE1M2JlZmNjZDI4Y2Q3ZiI6IlVLIn0sInBheXBhbE1lcmNoYW50SWQiOiJTb21lTWVyY2hhbnRJZCIsInNob3B3YXJlTWVyY2hhbnRJZCI6IjAxOTk4MGY5NDI2YzcxNmJhYTUzYmVmY2QwODc5ZmI0IiwiY2F0YWxvZ0Rvd25sb2FkVXJsIjoiaHR0cHM6Ly9leGFtcGxlLmNvbS90ZXN0L3BhdGgvZXhwb3J0In0.3K5rXCZGBgNFWOmZwTkVOV5AhCrr8VKgAS5ZPqsKeHI');
        $configServiceMock
            ->expects($this->once())
            ->method('delete')
            ->with(Settings::AGENTIC_COMMERCE_ONBOARDED);

        $loggerMock = $this->createMock(LoggerInterface::class);
        $loggerMock
            ->expects($this->once())
            ->method('log')
            ->with('info', 'PayPal agentic commerce webhook uninstall', [
                'success' => true,
                'message' => 'Merchant onboarded successfully',
                'error' => null,
            ]);

        $service = new HoneyWebhookService(
            $client,
            $this->createMock(EntityRepository::class),
            $this->createMock(CredentialsUtil::class),
            $this->createMock(RouterInterface::class),
            $configServiceMock,
            $loggerMock,
            $this->createMock(FaviconLoader::class)
        );

        $result = $service->deregister('019980f9426c716baa53befcd0879fb4');

        static::assertTrue($result->success);
        static::assertSame('Merchant onboarded successfully', $result->message);
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

        $client = $this->createMock(HoneyClientMock::class);
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
            ->method('get')
            ->with(Settings::AGENTIC_COMMERCE_ONBOARDED)
            ->willReturn('eyJ0eXAiOiJKV1QiLCJhbGciOiJIUzI1NiJ9.eyJzdG9yZU5hbWUiOiJTYWxlc0NoYW5uZWwgbmFtZSIsInN0b3JlVXJsIjoiaHR0cHM6Ly9leGFtcGxlLmNvbS8iLCJjb3VudHJ5IjoiREUiLCJjdXJyZW5jeSI6IkVVUiIsImZhdkljb24iOiJodHRwczovL2xvY2FsaG9zdC9mYXZpY29uLmljbyIsInNoaXBwaW5nQ291bnRyaWVzIjp7IjAxOTk4MGY5NDI2YzcxNmJhYTUzYmVmY2NjODRjNWM2IjoiREUiLCIwMTk5ODBmOTQyNmM3MTZiYWE1M2JlZmNjZDI4Y2Q3ZiI6IlVLIn0sInBheXBhbE1lcmNoYW50SWQiOiJTb21lTWVyY2hhbnRJZCIsInNob3B3YXJlTWVyY2hhbnRJZCI6IjAxOTk4MGY5NDI2YzcxNmJhYTUzYmVmY2QwODc5ZmI0IiwiY2F0YWxvZ0Rvd25sb2FkVXJsIjoiaHR0cHM6Ly9leGFtcGxlLmNvbS90ZXN0L3BhdGgvZXhwb3J0In0.3K5rXCZGBgNFWOmZwTkVOV5AhCrr8VKgAS5ZPqsKeHI');
        $configServiceMock
            ->expects($this->once())
            ->method('set')
            ->with(Settings::AGENTIC_COMMERCE_ONBOARDED);

        $loggerMock = $this->createMock(LoggerInterface::class);
        $loggerMock
            ->expects($this->exactly(2))
            ->method('log');

        $service = new HoneyWebhookService(
            $client,
            $salesChannelRepository,
            $credentialsUtil,
            $routeMock,
            $configServiceMock,
            $loggerMock,
            $this->createMock(FaviconLoader::class)
        );

        $result = $service->register($salesChannel->getId(), $context);

        static::assertTrue($result->success);
        static::assertSame('Merchant onboarded successfully', $result->message);
    }

    public function testFailedReRegister(): void
    {
        $this->expectException(HoneyWebhookException::class);
        $this->expectExceptionMessage('JWT signature verification failed');

        $context = Context::createCLIContext();
        $salesChannel = self::createSalesChannel();
        $salesChannelRepository = $this->createMock(EntityRepository::class);
        $salesChannelRepository
            ->expects($this->once())
            ->method('search')
            ->willReturn(new EntitySearchResult('sales_channel', 1, new SalesChannelCollection([$salesChannel]), null, new Criteria(), $context));

        $client = $this->createMock(HoneyClientMock::class);
        $client
            ->expects($this->exactly(2))
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
            ->method('get')
            ->with(Settings::AGENTIC_COMMERCE_ONBOARDED)
            ->willReturn('eyJ0eXAiOiJKV1QiLCJhbGciOiJIUzI1NiJ9.eyJzdG9yZU5hbWUiOiJTYWxlc0NoYW5uZWwgbmFtZSIsInN0b3JlVXJsIjoiaHR0cHM6Ly9leGFtcGxlLmNvbS8iLCJjb3VudHJ5IjoiREUiLCJjdXJyZW5jeSI6IkVVUiIsImZhdkljb24iOiJodHRwczovL2xvY2FsaG9zdC9mYXZpY29uLmljbyIsInNoaXBwaW5nQ291bnRyaWVzIjp7IjAxOTk4MGY5NDI2YzcxNmJhYTUzYmVmY2NjODRjNWM2IjoiREUiLCIwMTk5ODBmOTQyNmM3MTZiYWE1M2JlZmNjZDI4Y2Q3ZiI6IlVLIn0sInBheXBhbE1lcmNoYW50SWQiOiJTb21lTWVyY2hhbnRJZCIsInNob3B3YXJlTWVyY2hhbnRJZCI6IjAxOTk4MGY5NDI2YzcxNmJhYTUzYmVmY2QwODc5ZmI0IiwiY2F0YWxvZ0Rvd25sb2FkVXJsIjoiaHR0cHM6Ly9leGFtcGxlLmNvbS90ZXN0L3BhdGgvZXhwb3J0In0.3K5rXCZGBgNFWOmZwTkVOV5AhCrr8VKgAS5ZPqsKeHI');
        $configServiceMock
            ->expects($this->never())
            ->method('set')
            ->with(Settings::AGENTIC_COMMERCE_ONBOARDED, true);

        $loggerMock = $this->createMock(LoggerInterface::class);
        $loggerMock
            ->expects($this->exactly(2))
            ->method('log');

        $service = new HoneyWebhookService(
            $client,
            $salesChannelRepository,
            $this->createMock(CredentialsUtil::class),
            $routeMock,
            $configServiceMock,
            $loggerMock,
            $this->createMock(FaviconLoader::class)
        );

        $service->register($salesChannel->getId(), $context);
    }

    public function testDeregisterNotRegistered(): void
    {
        $this->expectException(HoneyWebhookException::class);
        $this->expectExceptionMessage('Sales channel is not registered and can\'t be deregistered');

        $client = $this->createMock(HoneyClientMock::class);
        $client
            ->expects($this->never())
            ->method('request');

        $configServiceMock = $this->createMock(SystemConfigService::class);
        $configServiceMock
            ->expects($this->once())
            ->method('get')
            ->with(Settings::AGENTIC_COMMERCE_ONBOARDED)
            ->willReturn(null);
        $configServiceMock
            ->expects($this->never())
            ->method('delete')
            ->with(Settings::AGENTIC_COMMERCE_ONBOARDED);

        $service = new HoneyWebhookService(
            $client,
            $this->createMock(EntityRepository::class),
            $this->createMock(CredentialsUtil::class),
            $this->createMock(RouterInterface::class),
            $configServiceMock,
            $this->createMock(LoggerInterface::class),
            $this->createMock(FaviconLoader::class)
        );

        $service->deregister(Uuid::randomHex());
    }

    #[DataProvider('dataProviderMissingSalesChannelDataRegister')]
    public function testMissingSalesChannelDataRegister(?SalesChannelEntity $salesChannel, string $exceptionMessage): void
    {
        $this->expectException(HoneyWebhookException::class);
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
            ->method('get')
            ->with(Settings::AGENTIC_COMMERCE_ONBOARDED)
            ->willReturn(null);

        $client = $this->createMock(HoneyClientMock::class);
        $client
            ->expects($this->never())
            ->method('request');

        $service = new HoneyWebhookService(
            $client,
            $salesChannelRepository,
            $this->createMock(CredentialsUtil::class),
            $routeMock,
            $configServiceMock,
            $this->createMock(LoggerInterface::class),
            $this->createMock(FaviconLoader::class)
        );

        $service->register($salesChannel->getId(), $context);
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

    public function testInvalidRegisterRequest(): void
    {
        $this->expectException(HoneyWebhookException::class);
        $this->expectExceptionMessage('JWT signature verification failed');

        $context = Context::createCLIContext();
        $salesChannel = self::createSalesChannel();
        $salesChannelRepository = $this->createMock(EntityRepository::class);
        $salesChannelRepository
            ->expects($this->once())
            ->method('search')
            ->willReturn(new EntitySearchResult('sales_channel', 1, new SalesChannelCollection([$salesChannel]), null, new Criteria(), $context));

        $client = $this->createMock(HoneyClientMock::class);
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
            ->method('get')
            ->with(Settings::AGENTIC_COMMERCE_ONBOARDED)
            ->willReturn(null);
        $configServiceMock
            ->expects($this->once())
            ->method('delete')
            ->with(Settings::AGENTIC_COMMERCE_ONBOARDED);

        $loggerMock = $this->createMock(LoggerInterface::class);
        $loggerMock
            ->expects($this->once())
            ->method('log')
            ->with('error', 'PayPal agentic commerce webhook install', [
                'success' => false,
                'message' => 'JWT signature verification failed',
                'error' => 'INVALID_JWT',
            ]);

        $service = new HoneyWebhookService(
            $client,
            $salesChannelRepository,
            $this->createMock(CredentialsUtil::class),
            $routeMock,
            $configServiceMock,
            $loggerMock,
            $this->createMock(FaviconLoader::class)
        );

        $service->register($salesChannel->getId(), $context);
    }

    public function testInvalidDeregisterRequest(): void
    {
        $this->expectException(HoneyWebhookException::class);
        $this->expectExceptionMessage('JWT signature verification failed');

        $client = $this->createMock(HoneyClientMock::class);
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

        $configServiceMock = $this->createMock(SystemConfigService::class);
        $configServiceMock
            ->expects($this->once())
            ->method('get')
            ->with(Settings::AGENTIC_COMMERCE_ONBOARDED)
            ->willReturn('eyJ0eXAiOiJKV1QiLCJhbGciOiJIUzI1NiJ9.eyJzdG9yZU5hbWUiOiJTYWxlc0NoYW5uZWwgbmFtZSIsInN0b3JlVXJsIjoiaHR0cHM6Ly9leGFtcGxlLmNvbS8iLCJjb3VudHJ5IjoiREUiLCJjdXJyZW5jeSI6IkVVUiIsImZhdkljb24iOiJodHRwczovL2xvY2FsaG9zdC9mYXZpY29uLmljbyIsInNoaXBwaW5nQ291bnRyaWVzIjp7IjAxOTk4MGY5NDI2YzcxNmJhYTUzYmVmY2NjODRjNWM2IjoiREUiLCIwMTk5ODBmOTQyNmM3MTZiYWE1M2JlZmNjZDI4Y2Q3ZiI6IlVLIn0sInBheXBhbE1lcmNoYW50SWQiOiJTb21lTWVyY2hhbnRJZCIsInNob3B3YXJlTWVyY2hhbnRJZCI6IjAxOTk4MGY5NDI2YzcxNmJhYTUzYmVmY2QwODc5ZmI0IiwiY2F0YWxvZ0Rvd25sb2FkVXJsIjoiaHR0cHM6Ly9leGFtcGxlLmNvbS90ZXN0L3BhdGgvZXhwb3J0In0.3K5rXCZGBgNFWOmZwTkVOV5AhCrr8VKgAS5ZPqsKeHI');
        $configServiceMock
            ->expects($this->never())
            ->method('set');

        $loggerMock = $this->createMock(LoggerInterface::class);
        $loggerMock
            ->expects($this->once())
            ->method('log')
            ->with('error', 'PayPal agentic commerce webhook uninstall', [
                'success' => false,
                'message' => 'JWT signature verification failed',
                'error' => 'INVALID_JWT',
            ]);

        $service = new HoneyWebhookService(
            $client,
            $this->createMock(EntityRepository::class),
            $this->createMock(CredentialsUtil::class),
            $this->createMock(RouterInterface::class),
            $configServiceMock,
            $loggerMock,
            $this->createMock(FaviconLoader::class)
        );

        $service->deregister(Uuid::randomHex());
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

        $client = $this->createMock(HoneyClientMock::class);
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
            ->method('get')
            ->with(Settings::AGENTIC_COMMERCE_ONBOARDED)
            ->willReturn(false);
        $configServiceMock
            ->expects($this->never())
            ->method('delete')
            ->with(Settings::AGENTIC_COMMERCE_ONBOARDED);

        $service = new HoneyWebhookService(
            $client,
            $salesChannelRepository,
            $this->createMock(CredentialsUtil::class),
            $routeMock,
            $configServiceMock,
            $this->createMock(LoggerInterface::class),
            $this->createMock(FaviconLoader::class)
        );

        $result = $service->register($salesChannel->getId(), $context);

        static::assertFalse($result->success);
        static::assertSame('INVALID_JWT', $result->error);
        static::assertSame('JWT signature verification failed', $result->message);
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
        $salesChannel->setId('019980f9426c716baa53befcd0879fb4');
        $salesChannel->setActive(true);
        $salesChannel->setTypeId(SwagPayPal::SALES_CHANNEL_TYPE_AGENTIC_COMMERCE);
        $salesChannel->setProductExports(new ProductExportCollection([$productExport]));
        $salesChannel->setCountry($de);
        $salesChannel->setCurrency($eur);
        $salesChannel->setTranslated(['name' => 'SalesChannel name']);

        return $salesChannel;
    }
}
