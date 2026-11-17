<?php declare(strict_types=1);
/*
 * (c) shopware AG <info@shopware.com>
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Swag\PayPal\Test\Checkout\SalesChannel;

use PHPUnit\Framework\TestCase;
use Shopware\Core\Framework\DataAbstractionLayer\EntityRepository;
use Shopware\Core\Framework\Log\Package;
use Shopware\Core\Framework\Test\TestCaseBase\KernelTestBehaviour;
use Shopware\Core\Test\Generator;
use Swag\PayPal\Checkout\SalesChannel\ClearVaultRoute;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\RouterInterface;

/**
 * @internal
 */
#[Package('checkout')]
class ClearVaultRouteTest extends TestCase
{
    use KernelTestBehaviour;

    public function testClearVault(): void
    {
        $salesChannelContext = Generator::createSalesChannelContext();
        $repo = $this->createMock(EntityRepository::class);
        $repo->expects(static::once())
            ->method('delete')
            ->with(
                [[
                    'paymentMethodId' => $salesChannelContext->getPaymentMethod()->getId(),
                    'customerId' => $salesChannelContext->getCustomerId(),
                ]],
                $salesChannelContext->getContext()
            );

        $route = new ClearVaultRoute($repo);

        $route->clearVault(new Request(), $salesChannelContext);
    }

    public function testSubscriptionRouteUsesSubscriptionContext(): void
    {
        $router = static::getContainer()->get('router');
        static::assertInstanceOf(RouterInterface::class, $router);
        $routes = $router->getRouteCollection();

        $route = $routes->get('store-api.subscription.paypal.vault.clear');
        static::assertNotNull($route);
        static::assertSame('/store-api/subscription/paypal/vault/clear', $route->getPath());
        static::assertSame(['POST'], $route->getMethods());
        static::assertSame(ClearVaultRoute::class . '::clearVault', $route->getDefault('_controller'));
        static::assertSame(['store-api'], $route->getDefault('_routeScope'));
        static::assertTrue($route->getDefault('_loginRequired'));

        // SwagCommercial passes the subscription context, and with it the payment method of the subscription, to these routes
        static::assertTrue($route->getDefault('_subscriptionCart'));
        static::assertTrue($route->getDefault('_subscriptionContext'));

        // the default route keeps clearing the vault of the main context
        $defaultRoute = $routes->get('store-api.paypal.vault.clear');
        static::assertNotNull($defaultRoute);
        static::assertNull($defaultRoute->getDefault('_subscriptionContext'));
    }
}
