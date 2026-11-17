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
use Shopware\Core\Framework\Log\Package;
use Shopware\Core\Framework\Test\TestCaseBase\KernelTestBehaviour;
use Shopware\Core\Framework\Uuid\Uuid;
use Shopware\Core\Test\Generator;
use Shopware\Core\Test\Stub\DataAbstractionLayer\StaticEntityRepository;
use Shopware\PayPalSDK\Struct\V1\Token;
use Swag\PayPal\Checkout\Exception\MissingCustomerVaultTokenException;
use Swag\PayPal\Checkout\SalesChannel\CustomerVaultTokenRoute;
use Swag\PayPal\DataAbstractionLayer\VaultToken\VaultTokenCollection;
use Swag\PayPal\DataAbstractionLayer\VaultToken\VaultTokenEntity;
use Swag\PayPal\RestApi\V1\Resource\TokenResource;
use Symfony\Component\Routing\RouterInterface;

/**
 * @internal
 */
#[Package('checkout')]
class CustomerVaultTokenRouteTest extends TestCase
{
    use KernelTestBehaviour;

    /**
     * @var StaticEntityRepository<VaultTokenCollection>
     */
    private StaticEntityRepository $repository;

    private TokenResource&MockObject $tokenResource;

    private CustomerVaultTokenRoute $route;

    protected function setUp(): void
    {
        $this->repository = new StaticEntityRepository([]);
        $this->tokenResource = $this->createMock(TokenResource::class);

        $this->route = new CustomerVaultTokenRoute($this->repository, $this->tokenResource);
    }

    public function testGetVaultTokenWithoutCustomer(): void
    {
        $salesChannelContext = Generator::generateSalesChannelContext();
        $salesChannelContext->assign(['customer' => null]);

        $this->expectException(CustomerException::class);

        $this->route->getVaultToken($salesChannelContext);
    }

    public function testGetVaultTokenWithGuestCustomer(): void
    {
        $salesChannelContext = Generator::generateSalesChannelContext();
        $salesChannelContext->getCustomer()?->setGuest(true);

        $this->expectException(CustomerException::class);

        $this->route->getVaultToken($salesChannelContext);
    }

    public function testGetVaultToken(): void
    {
        $salesChannelContext = Generator::generateSalesChannelContext();
        $salesChannelContext->getCustomer()?->setGuest(false);

        $this->repository->addSearch(new VaultTokenCollection([$this->createVaultToken()]));

        $token = new Token();
        $token->assign(['idToken' => 'dummy-token', 'expiresIn' => 45000]);

        $this->tokenResource->expects($this->once())
            ->method('getUserIdToken')
            ->with($salesChannelContext->getSalesChannelId(), 'token-customer-id')
            ->willReturn($token);

        $vaultToken = new VaultTokenEntity();
        $vaultToken->setId(Uuid::randomHex());
        $this->repository->addSearch(new VaultTokenCollection([$vaultToken]));

        $response = $this->route->getVaultToken($salesChannelContext);

        static::assertSame(
            $token->getIdToken(),
            $response->getToken()
        );
    }

    public function testVaultTokenIsNull(): void
    {
        $salesChannelContext = Generator::generateSalesChannelContext();
        $salesChannelContext->getCustomer()?->setGuest(false);

        $this->repository->addSearch(new VaultTokenCollection([$this->createVaultToken()]));

        $token = new Token();
        $token->assign(['idToken' => null, 'expiresIn' => 45000]);

        $this->tokenResource->expects($this->once())->method('getUserIdToken')->willReturn($token);

        $vaultToken = new VaultTokenEntity();
        $vaultToken->setId(Uuid::randomHex());
        $this->repository->addSearch(new VaultTokenCollection([$vaultToken]));

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

    private function createVaultToken(): VaultTokenEntity
    {
        $vaultToken = new VaultTokenEntity();
        $vaultToken->setId(Uuid::randomHex());
        $vaultToken->setTokenCustomer('token-customer-id');

        return $vaultToken;
    }
}
