<?php declare(strict_types=1);
/*
 * (c) shopware AG <info@shopware.com>
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Swag\PayPal\Tests\AgenticCommerce\Util;

use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;
use Shopware\Core\Framework\Context;
use Shopware\Core\Framework\Log\Package;
use Shopware\Core\Framework\Uuid\Uuid;
use Shopware\Core\System\SalesChannel\Context\SalesChannelContextService;
use Shopware\Core\System\SalesChannel\SalesChannelContext;
use Shopware\Storefront\Theme\AbstractResolvedConfigLoader;
use Shopware\Storefront\Theme\ConfigLoader\AbstractAvailableThemeProvider;
use Swag\PayPal\AgenticCommerce\Exception\HoneyWebhookException;
use Swag\PayPal\AgenticCommerce\Util\FaviconLoader;

/**
 * @internal
 */
#[Package('checkout')]
#[CoversClass(FaviconLoader::class)]
class FaviconLoaderTest extends TestCase
{
    public function testThemeIdNotFound(): void
    {
        $this->expectException(HoneyWebhookException::class);
        $this->expectExceptionMessage('Storefront sales channel not found');

        $themeProviderMock = $this->createMock(AbstractAvailableThemeProvider::class);
        $themeProviderMock
            ->expects($this->once())
            ->method('load')
            ->willReturn([]);

        $faviconLoader = new FaviconLoader(
            $themeProviderMock,
            $this->createMock(AbstractResolvedConfigLoader::class),
            $this->createMock(SalesChannelContextService::class),
        );

        $faviconLoader->loadFaviconLink(Uuid::randomHex(), Context::createCLIContext());
    }

    public function testLoadFaviconLink(): void
    {
        $themeId = Uuid::randomHex();
        $salesChannelId = Uuid::randomHex();
        $context = Context::createCLIContext();

        $themeProviderMock = $this->createMock(AbstractAvailableThemeProvider::class);
        $themeProviderMock
            ->expects($this->once())
            ->method('load')
            ->willReturn([$salesChannelId => $themeId]);

        $salesChannelMock = $this->createMock(SalesChannelContext::class);

        $contextServiceMock = $this->createMock(SalesChannelContextService::class);
        $contextServiceMock
            ->expects($this->once())
            ->method('get')
            ->willReturn($salesChannelMock);

        $configLoaderMock = $this->createMock(AbstractResolvedConfigLoader::class);
        $configLoaderMock
            ->expects($this->once())
            ->method('load')
            ->with($themeId, $salesChannelMock)
            ->willReturn(['sw-logo-favicon' => 'https://example.com/favicon.ico']);

        $faviconLoader = new FaviconLoader(
            $themeProviderMock,
            $configLoaderMock,
            $contextServiceMock,
        );

        $link = $faviconLoader->loadFaviconLink($salesChannelId, $context);

        static::assertSame('https://example.com/favicon.ico', $link);
    }

    public function testLoadFaviconEmptyLink(): void
    {
        $themeId = Uuid::randomHex();
        $salesChannelId = Uuid::randomHex();
        $context = Context::createCLIContext();

        $themeProviderMock = $this->createMock(AbstractAvailableThemeProvider::class);
        $themeProviderMock
            ->expects($this->once())
            ->method('load')
            ->willReturn([$salesChannelId => $themeId]);

        $salesChannelMock = $this->createMock(SalesChannelContext::class);

        $contextServiceMock = $this->createMock(SalesChannelContextService::class);
        $contextServiceMock
            ->expects($this->once())
            ->method('get')
            ->willReturn($salesChannelMock);

        $configLoaderMock = $this->createMock(AbstractResolvedConfigLoader::class);
        $configLoaderMock
            ->expects($this->once())
            ->method('load')
            ->with($themeId, $salesChannelMock)
            ->willReturn([]);

        $faviconLoader = new FaviconLoader(
            $themeProviderMock,
            $configLoaderMock,
            $contextServiceMock,
        );

        $link = $faviconLoader->loadFaviconLink($salesChannelId, $context);

        static::assertSame('', $link);
    }
}
