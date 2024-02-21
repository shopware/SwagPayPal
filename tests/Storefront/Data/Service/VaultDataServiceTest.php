<?php declare(strict_types=1);
/*
 * (c) shopware AG <info@shopware.com>
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Swag\PayPal\Test\Storefront\Data\Service;

use PHPUnit\Framework\TestCase;
use Shopware\Core\Checkout\Test\Cart\Common\Generator;
use Shopware\Core\Framework\DataAbstractionLayer\Search\Criteria;
use Shopware\Core\Framework\DataAbstractionLayer\Search\Filter\EqualsFilter;
use Shopware\Core\Framework\Log\Package;
use Shopware\Core\Framework\Uuid\Uuid;
use Shopware\Core\Test\Stub\DataAbstractionLayer\StaticEntityRepository;
use Swag\PayPal\DataAbstractionLayer\VaultToken\VaultTokenCollection;
use Swag\PayPal\DataAbstractionLayer\VaultToken\VaultTokenEntity;
use Swag\PayPal\RestApi\V1\Api\Token;
use Swag\PayPal\RestApi\V1\Resource\TokenResourceInterface;
use Swag\PayPal\Storefront\Data\Service\VaultDataService;
use Swag\PayPal\Util\Lifecycle\Method\AbstractMethodData;
use Swag\PayPal\Util\Lifecycle\Method\ACDCMethodData;
use Swag\PayPal\Util\Lifecycle\Method\PaymentMethodDataRegistry;

/**
 * @internal
 */
#[Package('checkout')]
class VaultDataServiceTest extends TestCase
{
    public function testBuildDataWithNonVaultablePaymentMethod(): void
    {
        $salesChannelContext = Generator::createSalesChannelContext();
        $salesChannelContext->getCustomer()?->setGuest(false);

        $method = $this->createMock(AbstractMethodData::class);
        $method
            ->expects(static::once())
            ->method('isVaultable')
            ->willReturn(false);
        $paymentMethodDataRegistry = $this->createMock(PaymentMethodDataRegistry::class);
        $paymentMethodDataRegistry
            ->expects(static::once())
            ->method('getPaymentMethodByHandler')
            ->with($salesChannelContext->getPaymentMethod()->getHandlerIdentifier())
            ->willReturn($method);

        $service = new VaultDataService(
            new StaticEntityRepository([]),
            $paymentMethodDataRegistry,
            $this->createMock(TokenResourceInterface::class),
        );

        $data = $service->buildData($salesChannelContext);
        static::assertNull($data);
    }

    public function testBuildDataWithoutCustomer(): void
    {
        $salesChannelContext = Generator::createSalesChannelContext();
        $salesChannelContext->assign(['customer' => null]);

        $service = new VaultDataService(
            new StaticEntityRepository([]),
            $this->createMock(PaymentMethodDataRegistry::class),
            $this->createMock(TokenResourceInterface::class),
        );

        $data = $service->buildData($salesChannelContext);
        static::assertNull($data);
    }

    public function testBuildDataWithGuestCustomer(): void
    {
        $salesChannelContext = Generator::createSalesChannelContext();
        $salesChannelContext->getCustomer()?->setGuest(true);

        $service = new VaultDataService(
            new StaticEntityRepository([]),
            $this->createMock(PaymentMethodDataRegistry::class),
            $this->createMock(TokenResourceInterface::class),
        );

        $data = $service->buildData($salesChannelContext);
        static::assertNull($data);
    }

    public function testBuildDataWithoutExistingToken(): void
    {
        $salesChannelContext = Generator::createSalesChannelContext();
        $salesChannelContext->getCustomer()?->setGuest(false);

        $method = $this->createMock(AbstractMethodData::class);
        $method
            ->expects(static::once())
            ->method('isVaultable')
            ->willReturn(true);
        $paymentMethodDataRegistry = $this->createMock(PaymentMethodDataRegistry::class);
        $paymentMethodDataRegistry
            ->expects(static::once())
            ->method('getPaymentMethodByHandler')
            ->with($salesChannelContext->getPaymentMethod()->getHandlerIdentifier())
            ->willReturn($method);

        $service = new VaultDataService(
            new StaticEntityRepository([new VaultTokenCollection()]),
            $paymentMethodDataRegistry,
            $this->createMock(TokenResourceInterface::class),
        );

        $data = $service->buildData($salesChannelContext);
        static::assertNotNull($data);
        static::assertNull($data->getIdentifier());
        static::assertSame('account', $data->getSnippetType());
    }

    public function testBuildDataWithExistingToken(): void
    {
        $salesChannelContext = Generator::createSalesChannelContext();
        $salesChannelContext->getCustomer()?->setGuest(false);

        $method = $this->createMock(ACDCMethodData::class);
        $method
            ->expects(static::once())
            ->method('isVaultable')
            ->willReturn(true);
        $paymentMethodDataRegistry = $this->createMock(PaymentMethodDataRegistry::class);
        $paymentMethodDataRegistry
            ->expects(static::once())
            ->method('getPaymentMethodByHandler')
            ->with($salesChannelContext->getPaymentMethod()->getHandlerIdentifier())
            ->willReturn($method);

        $existingToken = new VaultTokenEntity();
        $existingToken->setId(Uuid::randomHex());
        $existingToken->setIdentifier('test-identifier');

        $repository = new StaticEntityRepository([static function (Criteria $criteria) use ($existingToken, $salesChannelContext): VaultTokenCollection {
            static::assertInstanceOf(EqualsFilter::class, $criteria->getFilters()[0]);
            static::assertSame('mainMapping.customerId', $criteria->getFilters()[0]->getField());
            static::assertSame($salesChannelContext->getCustomerId(), $criteria->getFilters()[0]->getValue());

            static::assertInstanceOf(EqualsFilter::class, $criteria->getFilters()[1]);
            static::assertSame('mainMapping.paymentMethodId', $criteria->getFilters()[1]->getField());
            static::assertSame($salesChannelContext->getPaymentMethod()->getId(), $criteria->getFilters()[1]->getValue());

            return new VaultTokenCollection([$existingToken]);
        }]);

        $service = new VaultDataService(
            $repository,
            $paymentMethodDataRegistry,
            $this->createMock(TokenResourceInterface::class),
        );

        $data = $service->buildData($salesChannelContext);
        static::assertNotNull($data);
        static::assertSame('test-identifier', $data->getIdentifier());
        static::assertSame('card', $data->getSnippetType());
    }

    public function testGetUserIdTokenWithGuestCustomer(): void
    {
        $salesChannelContext = Generator::createSalesChannelContext();
        $salesChannelContext->getCustomer()?->setGuest(true);

        $service = new VaultDataService(
            new StaticEntityRepository([]),
            $this->createMock(PaymentMethodDataRegistry::class),
            $this->createMock(TokenResourceInterface::class),
        );

        $token = $service->getUserIdToken($salesChannelContext);
        static::assertNull($token);
    }

    public function testGetUserIdTokenWithExistingToken(): void
    {
        $salesChannelContext = Generator::createSalesChannelContext();
        $salesChannelContext->getCustomer()?->setGuest(false);

        $token = new Token();
        $token->setIdToken('test-id-token');
        $tokenResource = $this->createMock(TokenResourceInterface::class);
        $tokenResource
            ->expects(static::once())
            ->method('getUserIdToken')
            ->with($salesChannelContext->getSalesChannelId(), 'token-customer')
            ->willReturn($token);

        $existingToken = new VaultTokenEntity();
        $existingToken->setId(Uuid::randomHex());
        $existingToken->setTokenCustomer('token-customer');

        $repository = new StaticEntityRepository([static function (Criteria $criteria) use ($existingToken, $salesChannelContext): VaultTokenCollection {
            static::assertInstanceOf(EqualsFilter::class, $criteria->getFilters()[0]);
            static::assertSame('mainMapping.customerId', $criteria->getFilters()[0]->getField());
            static::assertSame($salesChannelContext->getCustomerId(), $criteria->getFilters()[0]->getValue());

            static::assertInstanceOf(EqualsFilter::class, $criteria->getFilters()[1]);
            static::assertSame('mainMapping.paymentMethodId', $criteria->getFilters()[1]->getField());
            static::assertSame($salesChannelContext->getPaymentMethod()->getId(), $criteria->getFilters()[1]->getValue());

            return new VaultTokenCollection([$existingToken]);
        }]);

        $service = new VaultDataService(
            $repository,
            $this->createMock(PaymentMethodDataRegistry::class),
            $tokenResource,
        );

        $token = $service->getUserIdToken($salesChannelContext);
        static::assertSame('test-id-token', $token);
    }

    public function testGetUserIdTokenWithoutExistingToken(): void
    {
        $salesChannelContext = Generator::createSalesChannelContext();
        $salesChannelContext->getCustomer()?->setGuest(false);

        $token = new Token();
        $token->setIdToken('test-id-token');
        $tokenResource = $this->createMock(TokenResourceInterface::class);
        $tokenResource
            ->expects(static::once())
            ->method('getUserIdToken')
            ->with($salesChannelContext->getSalesChannelId())
            ->willReturn($token);

        $service = new VaultDataService(
            new StaticEntityRepository([new VaultTokenCollection()]),
            $this->createMock(PaymentMethodDataRegistry::class),
            $tokenResource,
        );

        $token = $service->getUserIdToken($salesChannelContext);
        static::assertSame('test-id-token', $token);
    }
}
