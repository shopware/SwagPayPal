<?php declare(strict_types=1);
/*
 * (c) shopware AG <info@shopware.com>
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Swag\PayPal\Test\Webhook\Registration;

use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;
use Shopware\Core\Framework\Log\Package;
use Swag\PayPal\Webhook\Registration\WebhookUrlBuilder;
use Swag\PayPal\Webhook\Registration\WebhookUrlSource;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\Routing\Loader\ClosureLoader;
use Symfony\Component\Routing\RequestContext;
use Symfony\Component\Routing\Route;
use Symfony\Component\Routing\RouteCollection;
use Symfony\Component\Routing\Router;

/**
 * @internal
 */
#[Package('checkout')]
#[CoversClass(WebhookUrlBuilder::class)]
class WebhookUrlBuilderTest extends TestCase
{
    #[DataProvider('provideUrls')]
    public function testBuild(string $scheme, int $port, string $expectedHost): void
    {
        $router = $this->createRouter($scheme, $port);

        $url = (new WebhookUrlBuilder($router, $this->createRequestStack(false), ''))->build('token');

        static::assertSame('https://' . $expectedHost . '/api/_action/paypal/webhook/execute?sw-token=token', $url);
        static::assertSame($scheme, $router->getContext()->getScheme());
    }

    /**
     * The plugin registers HTTPS with the HTTPS port, not the port of the request.
     *
     * @return iterable<string, array{string, int, string}>
     */
    public static function provideUrls(): iterable
    {
        yield 'http on a custom port' => ['http', 8000, 'shop.example'];
        yield 'http on the default port' => ['http', 80, 'shop.example'];
        yield 'https on the default port' => ['https', 443, 'shop.example'];
        yield 'https on a custom port' => ['https', 8443, 'shop.example:8443'];
    }

    public function testSchemeIsRestoredWhenTheRouteIsMissing(): void
    {
        $router = new Router(
            new ClosureLoader(),
            static fn () => new RouteCollection(),
            [],
            new RequestContext(host: 'shop.example', scheme: 'http'),
        );

        try {
            (new WebhookUrlBuilder($router, $this->createRequestStack(false), ''))->build('token');
            static::fail('Expected an exception');
        } catch (\Exception) {
            static::assertSame('http', $router->getContext()->getScheme());
        }
    }

    public function testAdministrationSourceUsesTheRouterWithARequest(): void
    {
        $builder = new WebhookUrlBuilder($this->createRouter('http', 8000), $this->createRequestStack(true), 'https://app.example');

        static::assertSame('https://shop.example/api/_action/paypal/webhook/execute?sw-token=t', $builder->buildFor('t', WebhookUrlSource::Administration));
        static::assertSame('https://shop.example/api/_action/paypal/webhook/execute?sw-token=t', $builder->build('t'));
    }

    public function testAdministrationSourceFallsBackToTheAppUrlWithoutARequest(): void
    {
        $builder = new WebhookUrlBuilder($this->createRouter('http', 8000), $this->createRequestStack(false), 'https://app.example');

        static::assertSame('https://app.example/api/_action/paypal/webhook/execute?sw-token=t', $builder->build('t'));
    }

    public function testAdministrationSourceFallsBackToTheRouterWithoutARequestAndAppUrl(): void
    {
        $builder = new WebhookUrlBuilder($this->createRouter('http', 8000), $this->createRequestStack(false), '');

        static::assertSame('https://shop.example/api/_action/paypal/webhook/execute?sw-token=t', $builder->build('t'));
    }

    #[DataProvider('provideAppUrls')]
    public function testAppUrlSource(string $appUrl, ?string $expected): void
    {
        $router = $this->createRouter('http', 8000);
        $builder = new WebhookUrlBuilder($router, $this->createRequestStack(true), $appUrl);
        $context = $router->getContext();

        static::assertSame($expected, $builder->buildFor('t', WebhookUrlSource::AppUrl));
        static::assertSame($context, $router->getContext());
        static::assertSame('http', $context->getScheme());
        static::assertSame('shop.example', $context->getHost());
    }

    /**
     * @return iterable<string, array{string, string|null}>
     */
    public static function provideAppUrls(): iterable
    {
        yield 'https' => ['https://app.example', 'https://app.example/api/_action/paypal/webhook/execute?sw-token=t'];
        yield 'https on a custom port' => ['https://app.example:8443', 'https://app.example:8443/api/_action/paypal/webhook/execute?sw-token=t'];
        yield 'http drops its port' => ['http://app.example:8000', 'https://app.example/api/_action/paypal/webhook/execute?sw-token=t'];
        yield 'with a path' => ['https://app.example/shop', 'https://app.example/shop/api/_action/paypal/webhook/execute?sw-token=t'];
        yield 'empty' => ['', null];
        yield 'without a host' => ['/shop', null];
    }

    /**
     * @param array<string, string> $expected
     */
    #[DataProvider('provideAllUrls')]
    public function testBuildAll(string $appUrl, array $expected): void
    {
        $builder = new WebhookUrlBuilder($this->createRouter('http', 8000), $this->createRequestStack(true), $appUrl);

        static::assertSame($expected, $builder->buildAll('t'));
    }

    /**
     * @return iterable<string, array{string, array<string, string>}>
     */
    public static function provideAllUrls(): iterable
    {
        $administration = 'https://shop.example/api/_action/paypal/webhook/execute?sw-token=t';

        yield 'without an APP_URL' => ['', ['administration' => $administration]];
        yield 'with another host' => ['https://app.example', ['administration' => $administration, 'app_url' => 'https://app.example/api/_action/paypal/webhook/execute?sw-token=t']];
        yield 'with the same URL' => ['https://shop.example', ['administration' => $administration]];
    }

    public function testBuildAllWithoutARequestFallsBackToTheAppUrl(): void
    {
        $builder = new WebhookUrlBuilder($this->createRouter('http', 8000), $this->createRequestStack(false), 'https://app.example');

        static::assertSame(['administration' => 'https://app.example/api/_action/paypal/webhook/execute?sw-token=t'], $builder->buildAll('t'));
    }

    #[DataProvider('provideMaskedUrls')]
    public function testMaskToken(string $url, string $expected): void
    {
        static::assertSame($expected, WebhookUrlBuilder::maskToken($url));
    }

    /**
     * @return iterable<string, array{string, string}>
     */
    public static function provideMaskedUrls(): iterable
    {
        yield 'only parameter' => ['https://shop.example/execute?sw-token=abc123', 'https://shop.example/execute?sw-token=<token>'];
        yield 'with other parameters' => ['https://shop.example/execute?a=1&sw-token=abc123&b=2', 'https://shop.example/execute?a=1&sw-token=<token>&b=2'];
        yield 'without a token' => ['https://shop.example/execute', 'https://shop.example/execute'];
    }

    public function testWithSourceBuildsForTheSourceAndRestoresIt(): void
    {
        $builder = new WebhookUrlBuilder($this->createRouter('http', 8000), $this->createRequestStack(true), 'https://app.example');

        $url = $builder->withSource(WebhookUrlSource::AppUrl, static fn () => $builder->build('t'));

        static::assertSame('https://app.example/api/_action/paypal/webhook/execute?sw-token=t', $url);
        static::assertSame('https://shop.example/api/_action/paypal/webhook/execute?sw-token=t', $builder->build('t'));
    }

    public function testWithSourceRestoresTheSourceAfterAnException(): void
    {
        $builder = new WebhookUrlBuilder($this->createRouter('http', 8000), $this->createRequestStack(true), 'https://app.example');

        try {
            $builder->withSource(WebhookUrlSource::AppUrl, static fn () => throw new \RuntimeException('failed'));
        } catch (\RuntimeException) {
            static::assertSame('https://shop.example/api/_action/paypal/webhook/execute?sw-token=t', $builder->build('t'));
        }
    }

    private function createRequestStack(bool $withRequest): RequestStack
    {
        $stack = new RequestStack();
        if ($withRequest) {
            $stack->push(new Request());
        }

        return $stack;
    }

    private function createRouter(string $scheme, int $port): Router
    {
        $routes = new RouteCollection();
        $routes->add('api.action.paypal.webhook.execute', new Route('/api/_action/paypal/webhook/execute'));

        return new Router(
            new ClosureLoader(),
            static fn () => $routes,
            [],
            new RequestContext(host: 'shop.example', scheme: $scheme, httpPort: $scheme === 'http' ? $port : 80, httpsPort: $scheme === 'https' ? $port : 443),
        );
    }
}
