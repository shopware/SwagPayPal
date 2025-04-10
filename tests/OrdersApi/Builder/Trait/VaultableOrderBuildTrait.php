<?php declare(strict_types=1);
/*
 * (c) shopware AG <info@shopware.com>
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Swag\PayPal\Test\OrdersApi\Builder\Trait;

use PHPUnit\Framework\Attributes\Before;
use PHPUnit\Framework\MockObject\MockObject;
use Shopware\Commercial\Subscription\Checkout\Cart\Recurring\SubscriptionRecurringDataStruct;
use Shopware\Commercial\Subscription\Entity\Subscription\SubscriptionEntity;
use Shopware\Core\Checkout\Payment\Cart\Recurring\RecurringDataStruct;
use Shopware\Core\Checkout\Payment\Cart\SyncPaymentTransactionStruct;
use Shopware\Core\Framework\Uuid\Uuid;
use Shopware\Core\Framework\Validation\DataBag\RequestDataBag;
use Swag\PayPal\Checkout\Payment\Service\VaultTokenService;
use Swag\PayPal\DataAbstractionLayer\VaultToken\VaultTokenEntity;
use Swag\PayPal\OrdersApi\Builder\AbstractOrderBuilder;
use Swag\PayPal\Test\OrdersApi\Builder\AbstractOrderBuilderTestCase;

/**
 * @internal
 *
 * @phpstan-assert-if-true AbstractOrderBuilderTestCase $this
 */
trait VaultableOrderBuildTrait
{
    protected VaultTokenService&MockObject $vaultTokenService;

    public function testGetOrderRequestsVaultingWithSubscription(): void
    {
        if (!\class_exists(SubscriptionRecurringDataStruct::class) || !\class_exists(SubscriptionEntity::class)) {
            static::markTestSkipped('Commercial is not installed');
        }

        $paymentTransaction = new SyncPaymentTransactionStruct($this->createOrderTransaction(), $this->createOrder(), new RecurringDataStruct(Uuid::randomHex(), new \DateTime()));
        $salesChannelContext = $this->createSalesChannelContext();
        $customer = $salesChannelContext->getCustomer();
        static::assertNotNull($customer);

        $this->vaultTokenService->expects(static::once())->method('getAvailableToken')->willReturn(null);
        $this->vaultTokenService->expects(static::once())->method('getSubscription')->willReturn(new SubscriptionEntity());
        $this->vaultTokenService->expects(static::once())->method('requestVaulting');

        $this->getBuilder()->getOrder(
            $paymentTransaction,
            $salesChannelContext,
            new RequestDataBag(),
        );
    }

    public function testGetOrderRequestsVaultingWithUserRequest(): void
    {
        $paymentTransaction = new SyncPaymentTransactionStruct($this->createOrderTransaction(), $this->createOrder(), new RecurringDataStruct(Uuid::randomHex(), new \DateTime()));
        $salesChannelContext = $this->createSalesChannelContext();
        $customer = $salesChannelContext->getCustomer();
        static::assertNotNull($customer);

        $this->vaultTokenService->expects(static::once())->method('getAvailableToken')->willReturn(null);
        $this->vaultTokenService->expects(static::once())->method('requestVaulting');

        $this->getBuilder()->getOrder(
            $paymentTransaction,
            $salesChannelContext,
            new RequestDataBag([VaultTokenService::REQUEST_CREATE_VAULT => true]),
        );
    }

    public function testGetOrderUsesVaultTokenIfExists(): void
    {
        $paymentTransaction = new SyncPaymentTransactionStruct($this->createOrderTransaction(), $this->createOrder(), new RecurringDataStruct(Uuid::randomHex(), new \DateTime()));
        $salesChannelContext = $this->createSalesChannelContext();
        $customer = $salesChannelContext->getCustomer();
        static::assertNotNull($customer);

        $vaultToken = new VaultTokenEntity();
        $vaultToken->setToken('testToken');

        $this->vaultTokenService->expects(static::once())->method('getAvailableToken')->willReturn($vaultToken);

        $order = $this->getBuilder()->getOrder(
            $paymentTransaction,
            $salesChannelContext,
            new RequestDataBag([VaultTokenService::REQUEST_CREATE_VAULT => true]),
        );

        $paymentSource = $order->getPaymentSource()?->first($this->getPaymentSourceClass())?->jsonSerialize();

        static::assertSame('testToken', $paymentSource['vault_id'] ?? null);
    }

    public function testGetOrderNotUsesVaultTokenIfPreliminary(): void
    {
        $paymentTransaction = new SyncPaymentTransactionStruct($this->createOrderTransaction(), $this->createOrder(), new RecurringDataStruct(Uuid::randomHex(), new \DateTime()));
        $salesChannelContext = $this->createSalesChannelContext();
        $customer = $salesChannelContext->getCustomer();
        static::assertNotNull($customer);

        $this->vaultTokenService->expects(static::never())->method('getAvailableToken');

        $order = $this->getBuilder()->getOrder(
            $paymentTransaction,
            $salesChannelContext,
            new RequestDataBag([VaultTokenService::REQUEST_CREATE_VAULT => true, AbstractOrderBuilder::PRELIMINARY_ATTRIBUTE => true])
        );

        static::assertArrayNotHasKey('vault_id', $order->getPaymentSource()?->first($this->getPaymentSourceClass())?->jsonSerialize() ?? ['vault_id' => 'foo']);
    }

    #[Before]
    protected function setUpVaultTokenService(): void
    {
        $this->vaultTokenService = $this->createMock(VaultTokenService::class);
    }
}
