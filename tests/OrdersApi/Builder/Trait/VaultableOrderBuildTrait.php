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
use Shopware\Core\Checkout\Order\Aggregate\OrderTransaction\OrderTransactionEntity;
use Shopware\Core\Checkout\Order\OrderEntity;
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
        $this->vaultTokenService->expects(static::once())->method('getTokenForPayment')->willReturn(null);
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
        $this->vaultTokenService->expects(static::once())->method('getTokenForPayment')->willReturn(null);
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

        $this->vaultTokenService->expects(static::once())->method('getTokenForPayment')->willReturn($vaultToken);

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

    public function testGetOrderUsesTheSavedTokenAndRenewalsTheOneOfTheSubscription(): void
    {
        $orderTransaction = $this->createOrderTransaction();
        $order = $this->createOrder();
        $paymentTransaction = new PaymentTransactionStruct($orderTransaction->getId(), null, new RecurringDataStruct(Uuid::randomHex(), new \DateTime()));
        $context = Context::createDefaultContext();

        // the payer sees their saved token, while renewals keep the one the subscription was last paid with
        $this->vaultTokenService
            ->expects(static::exactly(2))
            ->method('getTokenForPayment')
            ->willReturnCallback(static function (PaymentTransactionStruct $struct, OrderTransactionEntity $orderTransaction, OrderEntity $order, Context $context, bool $isRecurring): VaultTokenEntity {
                $vaultToken = new VaultTokenEntity();
                $vaultToken->setToken($isRecurring ? 'subscriptionToken' : 'savedToken');

                return $vaultToken;
            });

        $payerSource = $this->getBuilder()
            ->getOrder($paymentTransaction, $orderTransaction, $order, $context, new Request())
            ->getPaymentSource()?->first($this->getPaymentSourceClass())?->jsonSerialize();
        $renewalSource = $this->getBuilder()
            ->getRecurringOrder($paymentTransaction, $orderTransaction, $order, $context)
            ->getPaymentSource()?->first($this->getPaymentSourceClass())?->jsonSerialize();

        static::assertSame('savedToken', $payerSource['vault_id'] ?? null);
        static::assertSame('subscriptionToken', $renewalSource['vault_id'] ?? null);
    }

    public function testGetOrderNotUsesVaultTokenIfPreliminary(): void
    {
        $orderTransaction = $this->createOrderTransaction();
        $order = $this->createOrder();
        $paymentTransaction = new PaymentTransactionStruct($orderTransaction->getId(), null, new RecurringDataStruct(Uuid::randomHex(), new \DateTime()));

        $this->vaultTokenService->expects(static::never())->method('getTokenForPayment');

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
