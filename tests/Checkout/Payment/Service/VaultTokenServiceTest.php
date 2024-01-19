<?php

declare(strict_types=1);
/*
 * (c) shopware AG <info@shopware.com>
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Swag\PayPal\Test\Checkout\Payment\Service;

use PHPUnit\Framework\TestCase;
use Shopware\Commercial\Subscription\Checkout\Cart\Recurring\SubscriptionRecurringDataStruct;
use Shopware\Commercial\Subscription\Entity\Subscription\SubscriptionDefinition;
use Shopware\Commercial\Subscription\Entity\Subscription\SubscriptionEntity;
use Shopware\Core\Checkout\Customer\CustomerDefinition;
use Shopware\Core\Checkout\Order\Aggregate\OrderCustomer\OrderCustomerEntity;
use Shopware\Core\Checkout\Order\Aggregate\OrderTransaction\OrderTransactionEntity;
use Shopware\Core\Checkout\Order\OrderEntity;
use Shopware\Core\Checkout\Payment\Cart\Recurring\RecurringDataStruct;
use Shopware\Core\Checkout\Payment\Cart\SyncPaymentTransactionStruct;
use Shopware\Core\Framework\Context;
use Shopware\Core\Framework\DataAbstractionLayer\Search\Criteria;
use Shopware\Core\Framework\DataAbstractionLayer\Search\Filter\EqualsFilter;
use Shopware\Core\Framework\Log\Package;
use Shopware\Core\Framework\Uuid\Uuid;
use Shopware\Core\Test\Generator;
use Shopware\Core\Test\Stub\DataAbstractionLayer\StaticEntityRepository;
use Swag\PayPal\Checkout\Exception\SubscriptionTypeNotSupportedException;
use Swag\PayPal\Checkout\Payment\Service\VaultTokenService;
use Swag\PayPal\DataAbstractionLayer\Extension\CustomerExtension;
use Swag\PayPal\DataAbstractionLayer\VaultToken\VaultTokenCollection;
use Swag\PayPal\DataAbstractionLayer\VaultToken\VaultTokenDefinition;
use Swag\PayPal\DataAbstractionLayer\VaultToken\VaultTokenEntity;
use Swag\PayPal\RestApi\V2\Api\Order\PaymentSource\Common\Attributes;
use Swag\PayPal\RestApi\V2\Api\Order\PaymentSource\Common\Attributes\Vault;
use Swag\PayPal\RestApi\V2\Api\Order\PaymentSource\Paypal;
use Symfony\Component\DependencyInjection\Exception\ServiceNotFoundException;

/**
 * @internal
 */
#[Package('checkout')]
class VaultTokenServiceTest extends TestCase
{
    public function testGetAvailableTokenFromSubscription(): void
    {
        if (!\class_exists(SubscriptionDefinition::class)) {
            static::markTestSkipped('Commercial is not available');
        }

        $token = new VaultTokenEntity();
        $token->setId(Uuid::randomHex());

        $subscription = new SubscriptionEntity();
        $subscription->setId(Uuid::randomHex());
        $subscription->setNextSchedule(new \DateTime());
        $subscription->setCustomFields(['swagPaypalVaultToken_payment-method-id' => $token->getId()]);

        $transaction = new OrderTransactionEntity();
        $transaction->setId(Uuid::randomHex());
        $transaction->setPaymentMethodId('payment-method-id');

        $order = new OrderEntity();
        $order->setId(Uuid::randomHex());
        $orderCustomer = new OrderCustomerEntity();
        $orderCustomer->setCustomerId('customer-id');
        $order->setOrderCustomer($orderCustomer);

        $vaultTokenRepository = new StaticEntityRepository([static function (Criteria $criteria) use ($token) {
            static::assertSame([$token->getId()], $criteria->getIds());
            static::assertCount(2, $criteria->getFilters());
            static::assertInstanceOf(EqualsFilter::class, $criteria->getFilters()[0]);
            static::assertSame('customerId', $criteria->getFilters()[0]->getField());
            static::assertSame('customer-id', $criteria->getFilters()[0]->getValue());
            static::assertInstanceOf(EqualsFilter::class, $criteria->getFilters()[1]);
            static::assertSame('paymentMethodId', $criteria->getFilters()[1]->getField());
            static::assertSame('payment-method-id', $criteria->getFilters()[1]->getValue());

            return new VaultTokenCollection([$token]);
        }], new VaultTokenDefinition());

        $vaultTokenService = new VaultTokenService(
            $vaultTokenRepository,
            new StaticEntityRepository([], new CustomerDefinition()),
            new StaticEntityRepository([], new CustomerDefinition())
        );

        static::assertSame($token, $vaultTokenService->getAvailableToken(
            new SyncPaymentTransactionStruct($transaction, $order, new SubscriptionRecurringDataStruct($subscription)),
            Context::createDefaultContext()
        ));
    }

    public function testGetAvailableTokenFromSubscriptionWithoutToken(): void
    {
        if (!\class_exists(SubscriptionDefinition::class)) {
            static::markTestSkipped('Commercial is not available');
        }

        $token = new VaultTokenEntity();
        $token->setId(Uuid::randomHex());

        $subscription = new SubscriptionEntity();
        $subscription->setId(Uuid::randomHex());
        $subscription->setNextSchedule(new \DateTime());

        $transaction = new OrderTransactionEntity();
        $transaction->setId(Uuid::randomHex());
        $transaction->setPaymentMethodId('payment-method-id');

        $order = new OrderEntity();
        $order->setId(Uuid::randomHex());
        $orderCustomer = new OrderCustomerEntity();
        $orderCustomer->setCustomerId('customer-id');
        $order->setOrderCustomer($orderCustomer);

        $vaultTokenService = new VaultTokenService(
            new StaticEntityRepository([], new CustomerDefinition()),
            new StaticEntityRepository([], new CustomerDefinition()),
            new StaticEntityRepository([], new CustomerDefinition()),
        );

        static::assertNull($vaultTokenService->getAvailableToken(
            new SyncPaymentTransactionStruct($transaction, $order, new SubscriptionRecurringDataStruct($subscription)),
            Context::createDefaultContext()
        ));
    }

    public function testGetAvailableTokenFromCustomer(): void
    {
        $token = new VaultTokenEntity();
        $token->setId(Uuid::randomHex());

        $transaction = new OrderTransactionEntity();
        $transaction->setId(Uuid::randomHex());
        $transaction->setPaymentMethodId('payment-method-id');

        $order = new OrderEntity();
        $order->setId(Uuid::randomHex());
        $orderCustomer = new OrderCustomerEntity();
        $orderCustomer->setCustomerId('customer-id');
        $order->setOrderCustomer($orderCustomer);

        $vaultTokenRepository = new StaticEntityRepository([static function (Criteria $criteria) use ($token) {
            static::assertCount(3, $criteria->getFilters());
            static::assertInstanceOf(EqualsFilter::class, $criteria->getFilters()[0]);
            static::assertSame('customerId', $criteria->getFilters()[0]->getField());
            static::assertSame('customer-id', $criteria->getFilters()[0]->getValue());
            static::assertInstanceOf(EqualsFilter::class, $criteria->getFilters()[1]);
            static::assertSame('paymentMethodId', $criteria->getFilters()[1]->getField());
            static::assertSame('payment-method-id', $criteria->getFilters()[1]->getValue());
            static::assertInstanceOf(EqualsFilter::class, $criteria->getFilters()[2]);
            static::assertSame('mainMapping.customerId', $criteria->getFilters()[2]->getField());
            static::assertSame('customer-id', $criteria->getFilters()[2]->getValue());

            return new VaultTokenCollection([$token]);
        }], new VaultTokenDefinition());

        $vaultTokenService = new VaultTokenService(
            $vaultTokenRepository,
            new StaticEntityRepository([], new CustomerDefinition()),
            null,
        );

        static::assertSame($token, $vaultTokenService->getAvailableToken(
            new SyncPaymentTransactionStruct($transaction, $order),
            Context::createDefaultContext()
        ));
    }

    public function testGetAvailableTokenWithoutCustomerIdInOrder(): void
    {
        if (!\class_exists(SubscriptionDefinition::class)) {
            static::markTestSkipped('Commercial is not available');
        }

        $vaultTokenService = new VaultTokenService(
            new StaticEntityRepository([], new VaultTokenDefinition()),
            new StaticEntityRepository([], new VaultTokenDefinition()),
            new StaticEntityRepository([], new VaultTokenDefinition())
        );

        static::assertNull($vaultTokenService->getAvailableToken(
            new SyncPaymentTransactionStruct(new OrderTransactionEntity(), new OrderEntity()),
            Context::createDefaultContext()
        ));
    }

    public function testRequestVaulting(): void
    {
        $paymentSource = new Paypal();
        $vaultTokenService = new VaultTokenService(
            new StaticEntityRepository([], new VaultTokenDefinition()),
            new StaticEntityRepository([], new VaultTokenDefinition()),
            new StaticEntityRepository([], new VaultTokenDefinition())
        );

        $vaultTokenService->requestVaulting($paymentSource);
        static::assertSame('ON_SUCCESS', $paymentSource->getAttributes()?->getVault()?->getStoreInVault());
        static::assertSame('MERCHANT', $paymentSource->getAttributes()->getVault()->getUsageType());
    }

    public function testGetSubscription(): void
    {
        if (!\class_exists(SubscriptionRecurringDataStruct::class)) {
            static::markTestSkipped('Commercial is not available');
        }

        $subscription = new SubscriptionEntity();
        $subscription->setId(Uuid::randomHex());
        $subscription->setNextSchedule(new \DateTime());

        $vaultTokenService = new VaultTokenService(
            new StaticEntityRepository([], new CustomerDefinition()),
            new StaticEntityRepository([], new CustomerDefinition()),
            new StaticEntityRepository([], new CustomerDefinition()),
        );

        static::assertSame($subscription, $vaultTokenService->getSubscription(
            new SyncPaymentTransactionStruct(new OrderTransactionEntity(), new OrderEntity(), new SubscriptionRecurringDataStruct($subscription)),
        ));
    }

    public function testGetSubscriptionNonRecurring(): void
    {
        $vaultTokenService = new VaultTokenService(
            new StaticEntityRepository([], new CustomerDefinition()),
            new StaticEntityRepository([], new CustomerDefinition()),
            new StaticEntityRepository([], new CustomerDefinition()),
        );

        static::assertNull($vaultTokenService->getSubscription(
            new SyncPaymentTransactionStruct(new OrderTransactionEntity(), new OrderEntity()),
        ));
    }

    public function testGetSubscriptionOfUnknownType(): void
    {
        $vaultTokenService = new VaultTokenService(
            new StaticEntityRepository([], new CustomerDefinition()),
            new StaticEntityRepository([], new CustomerDefinition()),
            new StaticEntityRepository([], new CustomerDefinition()),
        );

        $this->expectException(SubscriptionTypeNotSupportedException::class);
        $vaultTokenService->getSubscription(new SyncPaymentTransactionStruct(new OrderTransactionEntity(), new OrderEntity(), new RecurringDataStruct(Uuid::randomHex(), new \DateTime())));
    }

    public function testSaveTokenToCustomer(): void
    {
        $customerRepository = new StaticEntityRepository([], new CustomerDefinition());
        $vaultTokenRepository = new StaticEntityRepository([[]], new VaultTokenDefinition());

        $vaultTokenService = new VaultTokenService(
            $vaultTokenRepository,
            $customerRepository,
            new StaticEntityRepository([], new CustomerDefinition()),
        );

        $transaction = new OrderTransactionEntity();
        $transaction->setId(Uuid::randomHex());
        $transaction->setPaymentMethodId('payment-method-id');

        $order = new OrderEntity();
        $order->setId(Uuid::randomHex());
        $orderCustomer = new OrderCustomerEntity();
        $orderCustomer->setCustomerId('customer-id');
        $order->setOrderCustomer($orderCustomer);

        $salesChannelContext = Generator::createSalesChannelContext();
        $vault = new Vault();
        $vault->setId('vault-id');
        $attributes = new Attributes();
        $attributes->setVault($vault);
        $paymentSource = new Paypal();
        $paymentSource->setEmailAddress('test@hatoken.de');
        $paymentSource->setAttributes($attributes);
        $vaultTokenService->saveToken(new SyncPaymentTransactionStruct($transaction, $order), $paymentSource, $salesChannelContext);

        static::assertArrayHasKey('id', $vaultTokenRepository->upserts[0][0]);
        static::assertSame($vaultTokenRepository->upserts[0][0]['token'], 'vault-id');
        static::assertSame($vaultTokenRepository->upserts[0][0]['paymentMethodId'], $transaction->getPaymentMethodId());
        static::assertSame($vaultTokenRepository->upserts[0][0]['identifier'], $paymentSource->getVaultIdentifier());
        static::assertSame($vaultTokenRepository->upserts[0][0]['customerId'], $salesChannelContext->getCustomerId());

        static::assertSame([
            'id' => $salesChannelContext->getCustomerId(),
            CustomerExtension::CUSTOMER_VAULT_TOKEN_MAPPING_EXTENSION => [[
                'customerId' => $salesChannelContext->getCustomerId(),
                'paymentMethodId' => $transaction->getPaymentMethodId(),
                'tokenId' => $vaultTokenRepository->upserts[0][0]['id'],
            ]],
        ], $customerRepository->upserts[0][0]);
    }

    public function testSaveTokenToSubscription(): void
    {
        if (!\class_exists(SubscriptionDefinition::class)) {
            static::markTestSkipped('Commercial is not available');
        }

        $subscriptionRepository = new StaticEntityRepository([], new SubscriptionDefinition());
        $vaultTokenRepository = new StaticEntityRepository([[]], new VaultTokenDefinition());

        $vaultTokenService = new VaultTokenService(
            $vaultTokenRepository,
            new StaticEntityRepository([], new CustomerDefinition()),
            $subscriptionRepository,
        );

        $transaction = new OrderTransactionEntity();
        $transaction->setId(Uuid::randomHex());
        $transaction->setPaymentMethodId('payment-method-id');

        $subscription = new SubscriptionEntity();
        $subscription->setId(Uuid::randomHex());
        $subscription->setNextSchedule(new \DateTime());

        $salesChannelContext = Generator::createSalesChannelContext();
        $vault = new Vault();
        $vault->setId('vault-id');
        $attributes = new Attributes();
        $attributes->setVault($vault);
        $paymentSource = new Paypal();
        $paymentSource->setEmailAddress('test@hatoken.de');
        $paymentSource->setAttributes($attributes);
        $vaultTokenService->saveToken(new SyncPaymentTransactionStruct($transaction, new OrderEntity(), new SubscriptionRecurringDataStruct($subscription)), $paymentSource, $salesChannelContext);

        static::assertSame($vaultTokenRepository->upserts[0][0]['token'], 'vault-id');
        static::assertSame($vaultTokenRepository->upserts[0][0]['paymentMethodId'], $transaction->getPaymentMethodId());
        static::assertSame($vaultTokenRepository->upserts[0][0]['identifier'], $paymentSource->getVaultIdentifier());
        static::assertSame($vaultTokenRepository->upserts[0][0]['customerId'], $salesChannelContext->getCustomerId());

        static::assertSame([
            'id' => $subscription->getId(),
            'customFields' => [
                'swagPaypalVaultToken_payment-method-id' => $vaultTokenRepository->upserts[0][0]['id'],
            ],
        ], $subscriptionRepository->upserts[0][0]);
    }

    public function testSaveTokenToSubscriptionWithoutRepository(): void
    {
        if (!\class_exists(SubscriptionDefinition::class)) {
            static::markTestSkipped('Commercial is not available');
        }

        $vaultTokenRepository = new StaticEntityRepository([[]], new VaultTokenDefinition());

        $vaultTokenService = new VaultTokenService(
            $vaultTokenRepository,
            new StaticEntityRepository([], new CustomerDefinition()),
            null,
        );

        $transaction = new OrderTransactionEntity();
        $transaction->setId(Uuid::randomHex());
        $transaction->setPaymentMethodId('payment-method-id');

        $subscription = new SubscriptionEntity();
        $subscription->setId(Uuid::randomHex());
        $subscription->setNextSchedule(new \DateTime());

        $salesChannelContext = Generator::createSalesChannelContext();
        $vault = new Vault();
        $vault->setId('vault-id');
        $attributes = new Attributes();
        $attributes->setVault($vault);
        $paymentSource = new Paypal();
        $paymentSource->setEmailAddress('test@hatoken.de');
        $paymentSource->setAttributes($attributes);

        $this->expectException(ServiceNotFoundException::class);
        $vaultTokenService->saveToken(new SyncPaymentTransactionStruct($transaction, new OrderEntity(), new SubscriptionRecurringDataStruct($subscription)), $paymentSource, $salesChannelContext);
    }
}
