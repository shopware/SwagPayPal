<?php declare(strict_types=1);
/*
 * (c) shopware AG <info@shopware.com>
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Swag\PayPal\Test\Diagnostics\ExpressCallback;

use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;
use Psr\Log\NullLogger;
use Shopware\Core\Defaults;
use Shopware\Core\Framework\Context;
use Shopware\Core\Framework\Log\Package;
use Shopware\Core\Framework\Uuid\Uuid;
use Shopware\Core\System\SalesChannel\Aggregate\SalesChannelDomain\SalesChannelDomainCollection;
use Shopware\Core\System\SalesChannel\Aggregate\SalesChannelDomain\SalesChannelDomainEntity;
use Shopware\Core\System\SalesChannel\SalesChannelEntity;
use Swag\PayPal\Diagnostics\ExpressCallback\CallbackUrlBuilder;
use Swag\PayPal\Diagnostics\ExpressCallback\Recording\StoreApiUrlStorage;
use Swag\PayPal\Diagnostics\ExpressCallback\TrustedHosts;
use Swag\PayPal\Test\Mock\KeyValueStorageMock;
use Symfony\Component\Routing\Route;
use Symfony\Component\Routing\RouteCollection;
use Symfony\Component\Routing\RouterInterface;

/**
 * @internal
 */
#[Package('checkout')]
#[CoversClass(CallbackUrlBuilder::class)]
class CallbackUrlBuilderTest extends TestCase
{
    private const SALES_CHANNEL_ID = '98432def39fc4624b33213a56b8c944d';

    private const TOKEN = '3c7f1d2e9a8b4c5d6e7f8091a2b3c4d5';

    public function testStorefrontsGetTheCallbackOnEachDomain(): void
    {
        $urls = $this->createBuilder()->getCallbackUrls(
            $this->createSalesChannel(['https://shop.example', 'https://shop.example/', 'https://shop.example/en']),
            self::TOKEN,
            Context::createDefaultContext(),
        );

        static::assertSame([
            'https://shop.example/paypal/express/shipping-callback/' . self::SALES_CHANNEL_ID . '/' . self::TOKEN,
            'https://shop.example/en/paypal/express/shipping-callback/' . self::SALES_CHANNEL_ID . '/' . self::TOKEN,
        ], $urls);
    }

    public function testHeadlessSalesChannelsGetTheCallbackOnTheRecordedStoreApiUrl(): void
    {
        $urls = $this->createBuilder('https://api.shop.example/shop')->getCallbackUrls(
            $this->createSalesChannel(['https://shop.example'], Defaults::SALES_CHANNEL_TYPE_API),
            self::TOKEN,
            Context::createDefaultContext(),
        );

        static::assertSame(['https://api.shop.example/shop/store-api/paypal/express/shipping-callback/' . self::SALES_CHANNEL_ID . '/' . self::TOKEN], $urls);
    }

    public function testHeadlessSalesChannelsGetNoCallbackUrlOnAnUntrustedHost(): void
    {
        $builder = $this->createBuilder('https://evil.example', false);

        static::assertSame([], $builder->getCallbackUrls($this->createSalesChannel([], Defaults::SALES_CHANNEL_TYPE_API), self::TOKEN, Context::createDefaultContext()));
    }

    public function testHeadlessSalesChannelsWithoutRecordHaveNoCallbackUrl(): void
    {
        static::assertSame([], $this->createBuilder()->getCallbackUrls($this->createSalesChannel([], Defaults::SALES_CHANNEL_TYPE_API), self::TOKEN, Context::createDefaultContext()));
    }

    private function createBuilder(?string $storeApiUrl = null, bool $trusted = true): CallbackUrlBuilder
    {
        $storage = new StoreApiUrlStorage(new KeyValueStorageMock(), new NullLogger());
        if ($storeApiUrl !== null) {
            $storage->record(self::SALES_CHANNEL_ID, $storeApiUrl . '/store-api/paypal/express/shipping-callback/a/b');
        }

        $trustedHosts = $this->createMock(TrustedHosts::class);
        $trustedHosts->method('contains')->willReturn($trusted);

        return new CallbackUrlBuilder($this->createRouter(), $storage, $trustedHosts);
    }

    private function createRouter(): RouterInterface
    {
        $routes = new RouteCollection();
        $routes->add('frontend.paypal.express.shipping_callback', new Route('/paypal/express/shipping-callback/{salesChannelId}/{token}'));
        $routes->add('store-api.paypal.express.shipping_callback', new Route('/store-api/paypal/express/shipping-callback/{salesChannelId}/{token}'));

        $router = $this->createMock(RouterInterface::class);
        $router->method('getRouteCollection')->willReturn($routes);

        return $router;
    }

    /**
     * @param list<string> $domainUrls
     */
    private function createSalesChannel(array $domainUrls, string $typeId = Defaults::SALES_CHANNEL_TYPE_STOREFRONT): SalesChannelEntity
    {
        $domains = new SalesChannelDomainCollection();
        foreach ($domainUrls as $url) {
            $domain = new SalesChannelDomainEntity();
            $domain->setId(Uuid::randomHex());
            $domain->setUrl($url);
            $domains->add($domain);
        }

        $salesChannel = new SalesChannelEntity();
        $salesChannel->setId(self::SALES_CHANNEL_ID);
        $salesChannel->setTypeId($typeId);
        $salesChannel->setDomains($domains);

        return $salesChannel;
    }
}
