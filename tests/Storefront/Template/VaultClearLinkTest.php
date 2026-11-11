<?php declare(strict_types=1);
/*
 * (c) shopware AG <info@shopware.com>
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Swag\PayPal\Test\Storefront\Template;

use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;
use Shopware\Core\Checkout\Order\OrderEntity;
use Shopware\Core\Framework\Log\Package;
use Shopware\Core\Framework\Test\TestCaseBase\KernelTestBehaviour;
use Shopware\Storefront\Page\Account\Order\AccountEditOrderPage;
use Shopware\Storefront\Page\Checkout\Confirm\CheckoutConfirmPage;
use Swag\PayPal\Storefront\Data\Struct\VaultData;
use Symfony\Bridge\Twig\AppVariable;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\Routing\RouterInterface;
use Twig\Environment;

/**
 * @internal
 */
#[Package('checkout')]
class VaultClearLinkTest extends TestCase
{
    use KernelTestBehaviour;

    private const TEMPLATE = '@SwagPayPal/storefront/component/payment/payment-method.html.twig';
    private const BLOCK = 'component_payment_method_description_paypal_vault';

    /**
     * @param array<string, string> $pathParameters
     */
    #[DataProvider('dataProviderRoutes')]
    public function testClearLinkRedirectsBackToCurrentRoute(string $routeName, array $pathParameters, ?string $orderId, string $expectedPath): void
    {
        $router = static::getContainer()->get('router');
        static::assertInstanceOf(RouterInterface::class, $router);

        $route = $router->getRouteCollection()->get($routeName);
        if ($route === null) {
            static::markTestSkipped(\sprintf('Route "%s" is not available', $routeName));
        }

        // the route parameters of the current request contain the route defaults, as set by the Symfony router listener
        $routeParameters = \array_merge($route->getDefaults(), $pathParameters);
        unset($routeParameters['_controller']);

        $request = new Request();
        $request->attributes->set('_route', $routeName);
        $request->attributes->set('_route_params', $routeParameters);

        $page = new CheckoutConfirmPage();
        if ($orderId !== null) {
            $order = new OrderEntity();
            $order->setId($orderId);
            $page = new AccountEditOrderPage();
            $page->setOrder($order);
        }

        $vaultData = new VaultData();
        $vaultData->setIdentifier('Visa **1881');
        $vaultData->setSnippetType(VaultData::SNIPPET_TYPE_CARD);

        // the storefront app variable keeps the first request it returns, so every data set gets its own
        $requestStack = new RequestStack();
        $requestStack->push($request);
        $app = new AppVariable();
        $app->setRequestStack($requestStack);

        $twig = static::getContainer()->get('twig');
        static::assertInstanceOf(Environment::class, $twig);

        $html = $twig->load(self::TEMPLATE)->renderBlock(self::BLOCK, [
            'app' => $app,
            'payPalVaultData' => $vaultData,
            'activeRoute' => $routeName,
            'page' => $page,
        ]);

        static::assertSame(1, \preg_match('/<a href="([^"]+)"/', $html, $matches), $html);
        $clearUrl = \html_entity_decode($matches[1]);
        static::assertSame('/paypal/vault/clear', \parse_url($clearUrl, \PHP_URL_PATH));

        \parse_str((string) \parse_url($clearUrl, \PHP_URL_QUERY), $query);
        static::assertSame($routeName, $query['redirectTo'] ?? null);
        static::assertIsString($query['redirectParameters'] ?? null);

        // the vault clear controller redirects with these parameters, see StorefrontController::createActionResponse
        $redirectParameters = \json_decode($query['redirectParameters'], true, 512, \JSON_THROW_ON_ERROR);
        static::assertIsArray($redirectParameters);
        static::assertSame($expectedPath, $router->generate($routeName, $redirectParameters));
    }

    /**
     * @return iterable<string, array{0: string, 1: array<string, string>, 2: string|null, 3: string}>
     */
    public static function dataProviderRoutes(): iterable
    {
        yield 'checkout confirm page' => [
            'frontend.checkout.confirm.page',
            [],
            null,
            '/checkout/confirm',
        ];

        yield 'account edit order page' => [
            'frontend.account.edit-order.page',
            ['orderId' => 'b8bf1d9f9a6a4d0a8e3c1f2e4d5c6b7a'],
            'b8bf1d9f9a6a4d0a8e3c1f2e4d5c6b7a',
            '/account/order/edit/b8bf1d9f9a6a4d0a8e3c1f2e4d5c6b7a',
        ];

        yield 'route with another mandatory parameter' => [
            'frontend.detail.page',
            ['productId' => 'a1f2e3d4c5b6a7f8e9d0c1b2a3f4e5d6'],
            null,
            '/detail/a1f2e3d4c5b6a7f8e9d0c1b2a3f4e5d6',
        ];

        // only available with SwagCommercial
        yield 'subscription checkout confirm page' => [
            'frontend.subscription.checkout.confirm.page',
            ['subscriptionToken' => 'Gm0aJ8xVbPq3sLtC7wZnR2kYdE5hU9fA'],
            null,
            '/subscription/checkout/confirm/Gm0aJ8xVbPq3sLtC7wZnR2kYdE5hU9fA',
        ];
    }
}
