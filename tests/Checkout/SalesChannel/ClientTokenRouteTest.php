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
use Shopware\Core\Framework\DataAbstractionLayer\Search\Criteria;
use Shopware\Core\Framework\DataAbstractionLayer\Search\Filter\EqualsFilter;
use Shopware\Core\Framework\Log\Package;
use Shopware\Core\Framework\Uuid\Uuid;
use Shopware\Core\Test\Generator;
use Shopware\Core\Test\Stub\DataAbstractionLayer\StaticEntityRepository;
use Shopware\PayPalSDK\Struct\V1\Token;
use Swag\PayPal\Checkout\Exception\MissingCustomerVaultTokenException;
use Swag\PayPal\Checkout\SalesChannel\ClientTokenRoute;
use Swag\PayPal\Checkout\TokenResponse;
use Swag\PayPal\DataAbstractionLayer\VaultToken\VaultTokenCollection;
use Swag\PayPal\DataAbstractionLayer\VaultToken\VaultTokenEntity;
use Swag\PayPal\RestApi\V1\Resource\TokenResource;
use Swag\PayPal\Util\Lifecycle\Method\PaymentMethodDataRegistry;
use Swag\PayPal\Util\Lifecycle\Method\PayPalMethodData;
use Symfony\Component\HttpFoundation\Request;

/**
 * @internal
 */
#[Package('checkout')]
#[CoversClass(ClientTokenRoute::class)]
class ClientTokenRouteTest extends TestCase
{
    private const PAYPAL_PAYMENT_METHOD_ID = 'paypal-payment-method-id';

    /**
     * @var StaticEntityRepository<VaultTokenCollection>
     */
    private StaticEntityRepository $repository;

    private TokenResource&MockObject $tokenResource;

    private ClientTokenRoute $route;

    protected function setUp(): void
    {
        $this->repository = new StaticEntityRepository([]);
        $this->tokenResource = $this->createMock(TokenResource::class);

        $paymentMethodData = $this->createMock(PayPalMethodData::class);
        $paymentMethodDataRegistry = $this->createMock(PaymentMethodDataRegistry::class);
        $paymentMethodDataRegistry
            ->method('getPaymentMethod')
            ->with(PayPalMethodData::class)
            ->willReturn($paymentMethodData);
        $paymentMethodDataRegistry
            ->method('getEntityIdFromData')
            ->with($paymentMethodData)
            ->willReturn(self::PAYPAL_PAYMENT_METHOD_ID);

        $this->route = new ClientTokenRoute($this->tokenResource, $this->repository, $paymentMethodDataRegistry);
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
        $vaultToken->setId(Uuid::randomHex());
        $vaultToken->setToken('vault-id');

        $this->repository->addSearch(static function (Criteria $criteria) use ($salesChannelContext, $vaultToken): VaultTokenCollection {
            static::assertEquals([
                new EqualsFilter('mainMapping.customerId', $salesChannelContext->getCustomerId()),
                new EqualsFilter('mainMapping.paymentMethodId', self::PAYPAL_PAYMENT_METHOD_ID),
            ], $criteria->getFilters());

            return new VaultTokenCollection([$vaultToken]);
        });

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

        $this->repository->addSearch(new VaultTokenCollection());

        $this->tokenResource->expects($this->never())->method('getVaultClientToken');

        $this->expectException(MissingCustomerVaultTokenException::class);

        $this->route->getClientToken(new Request(request: [ClientTokenRoute::REQUEST_VAULT => true]), $salesChannelContext);
    }

    private function createToken(string $accessToken): Token
    {
        return (new Token())->assign(['accessToken' => $accessToken, 'expiresIn' => 900]);
    }
}
