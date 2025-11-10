<?php declare(strict_types=1);
/*
 * (c) shopware AG <info@shopware.com>
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Swag\PayPal\Test\OrdersApi\Builder\Trait;

use PHPUnit\Framework\Attributes\Before;
use PHPUnit\Framework\MockObject\MockObject;
use Shopware\Commercial\Subscription\Entity\Subscription\SubscriptionDefinition;
use Shopware\Core\Checkout\Payment\Cart\PaymentTransactionStruct;
use Shopware\Core\Checkout\Payment\Cart\Recurring\RecurringDataStruct;
use Shopware\Core\Framework\Context;
use Shopware\Core\Framework\Uuid\Uuid;
use Swag\PayPal\Checkout\Payment\Service\VaultTokenService;
use Swag\PayPal\DataAbstractionLayer\VaultToken\VaultTokenEntity;
use Swag\PayPal\OrdersApi\Builder\AbstractOrderBuilder;
use Swag\PayPal\Test\OrdersApi\Builder\AbstractOrderBuilderTestCase;
use Symfony\Component\HttpFoundation\Request;

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
        if (!\class_exists(SubscriptionDefinition::class)) {
            static::markTestSkipped('Commercial is not installed');
        }

        $orderTransaction = $this->createOrderTransaction();
        $order = $this->createOrder();
        $paymentTransaction = new PaymentTransactionStruct($orderTransaction->getId(), null, new RecurringDataStruct(Uuid::randomHex(), new \DateTime()));

        $this->vaultTokenService->expects(static::once())->method('shouldRequestVaulting')->willReturn(true);
        $this->vaultTokenService->expects(static::once())->method('getAvailableToken')->willReturn(null);
        $this->vaultTokenService->expects(static::once())->method('requestVaulting');

        $this->getBuilder()->getOrder(
            $paymentTransaction,
            $orderTransaction,
            $order,
            Context::createDefaultContext(),
            new Request(),
        );
    }

    public function testGetOrderRequestsVaultingWithUserRequest(): void
    {
        $orderTransaction = $this->createOrderTransaction();
        $order = $this->createOrder();
        $paymentTransaction = new PaymentTransactionStruct($orderTransaction->getId(), null, new RecurringDataStruct(Uuid::randomHex(), new \DateTime()));

        $this->vaultTokenService->expects(static::once())->method('shouldRequestVaulting')->willReturn(true);
        $this->vaultTokenService->expects(static::once())->method('getAvailableToken')->willReturn(null);
        $this->vaultTokenService->expects(static::once())->method('requestVaulting');

        $this->getBuilder()->getOrder(
            $paymentTransaction,
            $orderTransaction,
            $order,
            Context::createDefaultContext(),
            new Request([], [VaultTokenService::REQUEST_CREATE_VAULT => true]),
        );
    }

    public function testGetOrderUsesVaultTokenIfExists(): void
    {
        $orderTransaction = $this->createOrderTransaction();
        $order = $this->createOrder();
        $paymentTransaction = new PaymentTransactionStruct($orderTransaction->getId(), null, new RecurringDataStruct(Uuid::randomHex(), new \DateTime()));

        $vaultToken = new VaultTokenEntity();
        $vaultToken->setToken('testToken');

        $this->vaultTokenService->expects(static::once())->method('getAvailableToken')->willReturn($vaultToken);

        $order = $this->getBuilder()->getOrder(
            $paymentTransaction,
            $orderTransaction,
            $order,
            Context::createDefaultContext(),
            new Request([], [VaultTokenService::REQUEST_CREATE_VAULT => true]),
        );

        $paymentSource = $order->getPaymentSource()?->first($this->getPaymentSourceClass())?->jsonSerialize();

        static::assertSame('testToken', $paymentSource['vault_id'] ?? null);
    }

    public function testGetOrderNotUsesVaultTokenIfPreliminary(): void
    {
        $orderTransaction = $this->createOrderTransaction();
        $order = $this->createOrder();
        $paymentTransaction = new PaymentTransactionStruct($orderTransaction->getId(), null, new RecurringDataStruct(Uuid::randomHex(), new \DateTime()));

        $this->vaultTokenService->expects(static::never())->method('getAvailableToken');

        $order = $this->getBuilder()->getOrder(
            $paymentTransaction,
            $orderTransaction,
            $order,
            Context::createDefaultContext(),
            new Request([], [VaultTokenService::REQUEST_CREATE_VAULT => true], [AbstractOrderBuilder::PRELIMINARY_ATTRIBUTE => true]),
        );

        static::assertArrayNotHasKey('vault_id', $order->getPaymentSource()?->first($this->getPaymentSourceClass())?->jsonSerialize() ?? ['vault_id' => 'foo']);
    }

    #[Before]
    protected function setUpVaultTokenService(): void
    {
        $this->vaultTokenService = $this->createMock(VaultTokenService::class);
    }
}
