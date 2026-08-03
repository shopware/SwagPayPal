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
use Shopware\Core\Test\Generator;
use Shopware\Core\Test\Stub\DataAbstractionLayer\StaticEntityRepository;
use Shopware\PayPalSDK\Struct\V1\Token;
use Swag\PayPal\Checkout\Exception\MissingCustomerVaultTokenException;
use Swag\PayPal\Checkout\SalesChannel\CustomerVaultTokenRoute;
use Swag\PayPal\DataAbstractionLayer\VaultToken\VaultTokenCollection;
use Swag\PayPal\DataAbstractionLayer\VaultToken\VaultTokenEntity;
use Swag\PayPal\RestApi\V1\Resource\TokenResourceInterface;

/**
 * @internal
 */
#[Package('checkout')]
class CustomerVaultTokenRouteTest extends TestCase
{
    private TokenResourceInterface&MockObject $tokenResource;

    protected function setUp(): void
    {
        $this->tokenResource = $this->createMock(TokenResourceInterface::class);
    }

    public function testGetVaultTokenWithoutCustomer(): void
    {
        $salesChannelContext = Generator::generateSalesChannelContext();
        $salesChannelContext->assign(['customer' => null]);

        $this->expectException(CustomerException::class);

        $this->createRoute()->getVaultToken($salesChannelContext);
    }

    public function testGetVaultTokenWithGuestCustomer(): void
    {
        $salesChannelContext = Generator::generateSalesChannelContext();
        $salesChannelContext->getCustomer()?->setGuest(true);

        $this->expectException(CustomerException::class);

        $this->createRoute()->getVaultToken($salesChannelContext);
    }

    public function testGetVaultToken(): void
    {
        $salesChannelContext = Generator::generateSalesChannelContext();
        $salesChannelContext->getCustomer()?->setGuest(false);

        $token = new Token();
        $token->assign(['idToken' => 'dummy-token', 'expiresIn' => 45000]);

        $this->tokenResource->expects($this->once())->method('getUserIdToken')->willReturn($token);

        $response = $this->createRoute(new VaultTokenCollection([new VaultTokenEntity()]))
            ->getVaultToken($salesChannelContext);

        static::assertSame(
            $token->getIdToken(),
            $response->getToken()
        );
    }

    public function testVaultTokenIsNull(): void
    {
        $salesChannelContext = Generator::generateSalesChannelContext();
        $salesChannelContext->getCustomer()?->setGuest(false);

        $token = new Token();
        $token->assign(['idToken' => null, 'expiresIn' => 45000]);

        $this->tokenResource->expects($this->once())->method('getUserIdToken')->willReturn($token);

        $this->expectException(MissingCustomerVaultTokenException::class);

        $this->createRoute(new VaultTokenCollection([new VaultTokenEntity()]))
            ->getVaultToken($salesChannelContext);
    }

    private function createRoute(?VaultTokenCollection $tokens = null): CustomerVaultTokenRoute
    {
        $searches = $tokens !== null ? [$tokens] : [];

        return new CustomerVaultTokenRoute(
            new StaticEntityRepository($searches),
            $this->tokenResource,
        );
    }
}
