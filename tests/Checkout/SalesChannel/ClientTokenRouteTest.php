<?php declare(strict_types=1);
/*
 * (c) shopware AG <info@shopware.com>
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Swag\PayPal\Test\Checkout\SalesChannel;

use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Shopware\Core\Checkout\Customer\CustomerException;
use Shopware\Core\Framework\Log\Package;
use Shopware\Core\Test\Generator;
use Shopware\PayPalSDK\Struct\V1\Token;
use Swag\PayPal\Checkout\Exception\MissingCustomerVaultTokenException;
use Swag\PayPal\Checkout\Payment\Service\VaultTokenService;
use Swag\PayPal\Checkout\SalesChannel\ClientTokenRoute;
use Swag\PayPal\Checkout\TokenResponse;
use Swag\PayPal\DataAbstractionLayer\VaultToken\VaultTokenEntity;
use Swag\PayPal\RestApi\V1\Resource\TokenResource;
use Symfony\Component\HttpFoundation\Request;

/**
 * @internal
 */
#[Package('checkout')]
#[CoversClass(ClientTokenRoute::class)]
class ClientTokenRouteTest extends TestCase
{
    private TokenResource&MockObject $tokenResource;

    private VaultTokenService&MockObject $vaultTokenService;

    private ClientTokenRoute $route;

    protected function setUp(): void
    {
        $this->tokenResource = $this->createMock(TokenResource::class);
        $this->vaultTokenService = $this->createMock(VaultTokenService::class);

        $this->route = new ClientTokenRoute($this->tokenResource, $this->vaultTokenService);
    }

    public function testGetClientToken(): void
    {
        $salesChannelContext = Generator::generateSalesChannelContext();

        $this->tokenResource
            ->expects($this->once())
            ->method('getClientToken')
            ->with($salesChannelContext)
            ->willReturn($this->createToken('client-token'));
        $this->tokenResource->expects($this->never())->method('getVaultClientToken');

        $response = $this->route->getClientToken(new Request(), $salesChannelContext);

        static::assertInstanceOf(TokenResponse::class, $response);
        static::assertSame('client-token', $response->getToken());
    }

    public function testGetVaultClientToken(): void
    {
        $salesChannelContext = Generator::generateSalesChannelContext();
        $salesChannelContext->getCustomer()?->setGuest(false);

        $vaultToken = new VaultTokenEntity();
        $vaultToken->setToken('vault-id');
        $this->vaultTokenService
            ->expects($this->once())
            ->method('getCustomerToken')
            ->with($salesChannelContext->getCustomerId(), $salesChannelContext->getPaymentMethod()->getId(), $salesChannelContext->getContext())
            ->willReturn($vaultToken);

        $this->tokenResource->expects($this->never())->method('getClientToken');
        $this->tokenResource
            ->expects($this->once())
            ->method('getVaultClientToken')
            ->with($salesChannelContext, 'vault-id')
            ->willReturn($this->createToken('vault-client-token'));

        $response = $this->route->getClientToken(new Request(request: [ClientTokenRoute::REQUEST_VAULT => true]), $salesChannelContext);

        static::assertInstanceOf(TokenResponse::class, $response);
        static::assertSame('vault-client-token', $response->getToken());
    }

    public function testGetVaultClientTokenWithoutCustomer(): void
    {
        $salesChannelContext = Generator::generateSalesChannelContext();
        $salesChannelContext->assign(['customer' => null]);

        $this->tokenResource->expects($this->never())->method('getVaultClientToken');

        $this->expectException(CustomerException::class);

        $this->route->getClientToken(new Request(request: [ClientTokenRoute::REQUEST_VAULT => true]), $salesChannelContext);
    }

    public function testGetVaultClientTokenWithGuestCustomer(): void
    {
        $salesChannelContext = Generator::generateSalesChannelContext();
        $salesChannelContext->getCustomer()?->setGuest(true);

        $this->tokenResource->expects($this->never())->method('getVaultClientToken');

        $this->expectException(CustomerException::class);

        $this->route->getClientToken(new Request(request: [ClientTokenRoute::REQUEST_VAULT => true]), $salesChannelContext);
    }

    public function testGetVaultClientTokenWithoutVault(): void
    {
        $salesChannelContext = Generator::generateSalesChannelContext();
        $salesChannelContext->getCustomer()?->setGuest(false);

        $this->vaultTokenService->expects($this->once())->method('getCustomerToken')->willReturn(null);

        $this->tokenResource->expects($this->never())->method('getVaultClientToken');

        $this->expectException(MissingCustomerVaultTokenException::class);

        $this->route->getClientToken(new Request(request: [ClientTokenRoute::REQUEST_VAULT => true]), $salesChannelContext);
    }

    private function createToken(string $accessToken): Token
    {
        return (new Token())->assign(['accessToken' => $accessToken, 'expiresIn' => 900]);
    }
}
