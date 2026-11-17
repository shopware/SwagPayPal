<?php declare(strict_types=1);
/*
 * (c) shopware AG <info@shopware.com>
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Swag\PayPal\Test\Checkout\SalesChannel;

use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Shopware\Core\Checkout\Customer\CustomerException;
use Shopware\Core\Framework\DataAbstractionLayer\EntityRepository;
use Shopware\Core\Framework\DataAbstractionLayer\Search\EntitySearchResult;
use Shopware\Core\Framework\Log\Package;
use Shopware\Core\Framework\Test\TestCaseBase\KernelTestBehaviour;
use Shopware\Core\Test\Generator;
use Swag\PayPal\Checkout\Exception\MissingCustomerVaultTokenException;
use Swag\PayPal\Checkout\SalesChannel\CustomerVaultTokenRoute;
use Swag\PayPal\DataAbstractionLayer\VaultToken\VaultTokenEntity;
use Swag\PayPal\RestApi\V1\Api\Token;
use Swag\PayPal\RestApi\V1\Resource\TokenResourceInterface;
use Symfony\Component\Routing\RouterInterface;

/**
 * @internal
 */
#[Package('checkout')]
class CustomerVaultTokenRouteTest extends TestCase
{
    use KernelTestBehaviour;

    private EntityRepository&MockObject $repository;

    private TokenResourceInterface&MockObject $tokenResource;

    private CustomerVaultTokenRoute $route;

    protected function setUp(): void
    {
        $this->repository = $this->createMock(EntityRepository::class);
        $this->tokenResource = $this->createMock(TokenResourceInterface::class);

        $this->route = new CustomerVaultTokenRoute($this->repository, $this->tokenResource);
    }

    public function testGetVaultTokenWithoutCustomer(): void
    {
        $salesChannelContext = Generator::createSalesChannelContext();
        $salesChannelContext->assign(['customer' => null]);

        $this->expectException(CustomerException::class);

        $this->route->getVaultToken($salesChannelContext);
    }

    public function testGetVaultTokenWithGuestCustomer(): void
    {
        $salesChannelContext = Generator::createSalesChannelContext();
        $salesChannelContext->getCustomer()?->setGuest(true);

        $this->expectException(CustomerException::class);

        $this->route->getVaultToken($salesChannelContext);
    }

    public function testGetVaultToken(): void
    {
        $salesChannelContext = Generator::createSalesChannelContext();
        $salesChannelContext->getCustomer()?->setGuest(false);

        $entitySearchResult = $this->createMock(EntitySearchResult::class);
        $this->repository->expects(static::once())->method('search')->willReturn($entitySearchResult);
        $entitySearchResult->expects(static::once())->method('first')->willReturn(new VaultTokenEntity());

        $token = new Token();
        $token->assign(['idToken' => 'dummy-token', 'expiresIn' => 45000]);

        $this->tokenResource->expects(static::once())->method('getUserIdToken')->willReturn($token);

        $response = $this->route->getVaultToken($salesChannelContext);

        static::assertSame(
            $token->getIdToken(),
            $response->getToken()
        );
    }

    public function testVaultTokenIsNull(): void
    {
        $salesChannelContext = Generator::createSalesChannelContext();
        $salesChannelContext->getCustomer()?->setGuest(false);

        $entitySearchResult = $this->createMock(EntitySearchResult::class);
        $this->repository->expects(static::once())->method('search')->willReturn($entitySearchResult);
        $entitySearchResult->expects(static::once())->method('first')->willReturn(new VaultTokenEntity());

        $token = new Token();
        $token->assign(['idToken' => null, 'expiresIn' => 45000]);

        $this->tokenResource->expects(static::once())->method('getUserIdToken')->willReturn($token);

        $this->expectException(MissingCustomerVaultTokenException::class);

        $this->route->getVaultToken($salesChannelContext);
    }

    public function testSubscriptionRouteUsesSubscriptionContext(): void
    {
        $router = static::getContainer()->get('router');
        static::assertInstanceOf(RouterInterface::class, $router);
        $routes = $router->getRouteCollection();

        $route = $routes->get('store-api.subscription.paypal.vault.token');
        static::assertNotNull($route);
        static::assertSame('/store-api/subscription/paypal/vault-token', $route->getPath());
        static::assertSame(['GET'], $route->getMethods());
        static::assertSame(CustomerVaultTokenRoute::class . '::getVaultToken', $route->getDefault('_controller'));
        static::assertSame(['store-api'], $route->getDefault('_routeScope'));
        static::assertTrue($route->getDefault('_loginRequired'));
        static::assertFalse($route->getDefault('_loginRequiredAllowGuest'));

        // SwagCommercial passes the subscription context, and with it the payment method of the subscription, to these routes
        static::assertTrue($route->getDefault('_subscriptionCart'));
        static::assertTrue($route->getDefault('_subscriptionContext'));

        // the default route keeps returning the vault token of the main context
        $defaultRoute = $routes->get('store-api.paypal.vault.token');
        static::assertNotNull($defaultRoute);
        static::assertNull($defaultRoute->getDefault('_subscriptionContext'));
    }
}
