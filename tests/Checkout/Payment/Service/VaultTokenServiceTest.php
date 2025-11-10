<?php declare(strict_types=1);
/*
 * (c) shopware AG <info@shopware.com>
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Swag\PayPal\Test\Checkout\Payment\Service;

use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;
use Shopware\Commercial\Subscription\Checkout\Cart\Recurring\SubscriptionRecurringDataStruct;
use Shopware\Commercial\Subscription\Checkout\Cart\Recurring\SubscriptionsRecurringDataStruct;
use Shopware\Commercial\Subscription\Entity\Subscription\SubscriptionCollection;
use Shopware\Commercial\Subscription\Entity\Subscription\SubscriptionDefinition;
use Shopware\Commercial\Subscription\Entity\Subscription\SubscriptionEntity;
use Shopware\Commercial\Subscription\Framework\Struct\PlanIntervalMappingStruct;
use Shopware\Core\Checkout\Customer\CustomerCollection;
use Shopware\Core\Checkout\Customer\CustomerDefinition;
use Shopware\Core\Checkout\Order\Aggregate\OrderCustomer\OrderCustomerEntity;
use Shopware\Core\Checkout\Order\Aggregate\OrderTransaction\OrderTransactionEntity;
use Shopware\Core\Checkout\Order\OrderEntity;
use Shopware\Core\Checkout\Payment\Cart\PaymentTransactionStruct;
use Shopware\Core\Checkout\Payment\Cart\Recurring\RecurringDataStruct;
use Shopware\Core\Framework\Context;
use Shopware\Core\Framework\DataAbstractionLayer\Search\Criteria;
use Shopware\Core\Framework\DataAbstractionLayer\Search\Filter\EqualsFilter;
use Shopware\Core\Framework\Log\Package;
use Shopware\Core\Framework\Struct\ArrayStruct;
use Shopware\Core\Framework\Uuid\Uuid;
use Shopware\Core\Framework\Validation\DataBag\RequestDataBag;
use Shopware\Core\System\SalesChannel\SalesChannelContext;
use Shopware\Core\Test\Generator;
use Shopware\Core\Test\Stub\DataAbstractionLayer\StaticEntityRepository;
use Shopware\PayPalSDK\Struct\V2\Order\PaymentSource\Common\Attributes;
use Shopware\PayPalSDK\Struct\V2\Order\PaymentSource\Common\Attributes\Vault;
use Shopware\PayPalSDK\Struct\V2\Order\PaymentSource\Paypal;
use Swag\PayPal\Checkout\Exception\SubscriptionTypeNotSupportedException;
use Swag\PayPal\Checkout\Payment\Service\VaultTokenService;
use Swag\PayPal\DataAbstractionLayer\Extension\CustomerExtension;
use Swag\PayPal\DataAbstractionLayer\VaultToken\VaultTokenCollection;
use Swag\PayPal\DataAbstractionLayer\VaultToken\VaultTokenDefinition;
use Swag\PayPal\DataAbstractionLayer\VaultToken\VaultTokenEntity;
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

        /** @var StaticEntityRepository<VaultTokenCollection> $vaultTokenRepository */
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

        /** @var StaticEntityRepository<CustomerCollection> $customerRepository */
        $customerRepository = new StaticEntityRepository([], new CustomerDefinition());
        /** @var StaticEntityRepository<SubscriptionCollection> $subscriptionRepository */
        $subscriptionRepository = new StaticEntityRepository([], new SubscriptionDefinition());

        $vaultTokenService = new VaultTokenService(
            $vaultTokenRepository,
            $customerRepository,
            $subscriptionRepository,
        );

        /** @deprecated tag:v11.0.0 - Condition will always be true */
        if (\class_exists(SubscriptionsRecurringDataStruct::class)) {
            $recurring = new SubscriptionsRecurringDataStruct(new SubscriptionCollection([$subscription]));
        } else {
            $recurring = new SubscriptionRecurringDataStruct($subscription);
        }

        static::assertSame($token, $vaultTokenService->getAvailableToken(
            new PaymentTransactionStruct($transaction->getId(), recurring: $recurring),
            $transaction,
            $order,
            Context::createDefaultContext()
        ));
    }

    public function testGetAvailableTokenFromSubscriptionWithoutToken(): void
    {
        if (!\class_exists(SubscriptionDefinition::class)) {
            static::markTestSkipped('Commercial is not available');
        }

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

        /** @var StaticEntityRepository<VaultTokenCollection> $vaultTokenRepository */
        $vaultTokenRepository = new StaticEntityRepository([static function (Criteria $criteria) {
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

            return new VaultTokenCollection([]);
        }], new VaultTokenDefinition());

        /** @var StaticEntityRepository<CustomerCollection> $customerRepository */
        $customerRepository = new StaticEntityRepository([], new CustomerDefinition());
        /** @var StaticEntityRepository<SubscriptionCollection> $subscriptionRepository */
        $subscriptionRepository = new StaticEntityRepository([], new SubscriptionDefinition());

        $vaultTokenService = new VaultTokenService(
            $vaultTokenRepository,
            $customerRepository,
            $subscriptionRepository,
        );

        /** @deprecated tag:v11.0.0 - Condition will always be true */
        if (\class_exists(SubscriptionsRecurringDataStruct::class)) {
            $recurring = new SubscriptionsRecurringDataStruct(new SubscriptionCollection([$subscription]));
        } else {
            $recurring = new SubscriptionRecurringDataStruct($subscription);
        }

        static::assertNull($vaultTokenService->getAvailableToken(
            new PaymentTransactionStruct($transaction->getId(), recurring: $recurring),
            $transaction,
            $order,
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

        /** @var StaticEntityRepository<VaultTokenCollection> $vaultTokenRepository */
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

        /** @var StaticEntityRepository<CustomerCollection> $customerRepository */
        $customerRepository = new StaticEntityRepository([], new CustomerDefinition());

        $vaultTokenService = new VaultTokenService(
            $vaultTokenRepository,
            $customerRepository,
            null,
        );

        static::assertSame($token, $vaultTokenService->getAvailableToken(
            new PaymentTransactionStruct($transaction->getId()),
            $transaction,
            $order,
            Context::createDefaultContext()
        ));
    }

    public function testGetAvailableTokenWithoutCustomerIdInOrder(): void
    {
        if (!\class_exists(SubscriptionDefinition::class)) {
            static::markTestSkipped('Commercial is not available');
        }

        /** @var StaticEntityRepository<VaultTokenCollection> $vaultTokenRepository */
        $vaultTokenRepository = new StaticEntityRepository([], new VaultTokenDefinition());
        /** @var StaticEntityRepository<CustomerCollection> $customerRepository */
        $customerRepository = new StaticEntityRepository([], new CustomerDefinition());
        /** @var StaticEntityRepository<SubscriptionCollection> $subscriptionRepository */
        $subscriptionRepository = new StaticEntityRepository([], new SubscriptionDefinition());

        $vaultTokenService = new VaultTokenService(
            $vaultTokenRepository,
            $customerRepository,
            $subscriptionRepository,
        );

        static::assertNull($vaultTokenService->getAvailableToken(
            new PaymentTransactionStruct(Uuid::randomHex()),
            new OrderTransactionEntity(),
            new OrderEntity(),
            Context::createDefaultContext()
        ));
    }

    public function testRequestVaulting(): void
    {
        $paymentSource = new Paypal();

        /** @var StaticEntityRepository<VaultTokenCollection> $vaultTokenRepository */
        $vaultTokenRepository = new StaticEntityRepository([], new VaultTokenDefinition());
        /** @var StaticEntityRepository<CustomerCollection> $customerRepository */
        $customerRepository = new StaticEntityRepository([], new CustomerDefinition());
        /** @var StaticEntityRepository<SubscriptionCollection> $subscriptionRepository */
        $subscriptionRepository = new StaticEntityRepository([]);

        $vaultTokenService = new VaultTokenService(
            $vaultTokenRepository,
            $customerRepository,
            $subscriptionRepository,
        );

        $vaultTokenService->requestVaulting($paymentSource);
        static::assertSame('ON_SUCCESS', $paymentSource->getAttributes()?->getVault()?->getStoreInVault());
        static::assertSame('MERCHANT', $paymentSource->getAttributes()->getVault()->getUsageType());
    }

    public function testGetSubscription(): void
    {
        if (!\class_exists(SubscriptionDefinition::class)) {
            static::markTestSkipped('Commercial is not available');
        }

        $subscription = new SubscriptionEntity();
        $subscription->setId(Uuid::randomHex());
        $subscription->setNextSchedule(new \DateTime());
        $subscriptions = new SubscriptionCollection([$subscription]);

        /** @var StaticEntityRepository<VaultTokenCollection> $vaultTokenRepository */
        $vaultTokenRepository = new StaticEntityRepository([], new VaultTokenDefinition());
        /** @var StaticEntityRepository<CustomerCollection> $customerRepository */
        $customerRepository = new StaticEntityRepository([], new CustomerDefinition());
        /** @var StaticEntityRepository<SubscriptionCollection> $subscriptionRepository */
        $subscriptionRepository = new StaticEntityRepository([], new SubscriptionDefinition());

        $vaultTokenService = new VaultTokenService(
            $vaultTokenRepository,
            $customerRepository,
            $subscriptionRepository,
        );

        /** @deprecated tag:v11.0.0 - Condition will always be true */
        if (\class_exists(SubscriptionsRecurringDataStruct::class)) {
            $recurring = new SubscriptionsRecurringDataStruct($subscriptions);
        } else {
            $recurring = new SubscriptionRecurringDataStruct($subscription);
        }

        static::assertEquals($subscriptions, $vaultTokenService->getSubscriptions(
            new PaymentTransactionStruct(Uuid::randomHex(), recurring: $recurring),
        ));
    }

    public function testGetSubscriptionNonRecurring(): void
    {
        /** @var StaticEntityRepository<VaultTokenCollection> $vaultTokenRepository */
        $vaultTokenRepository = new StaticEntityRepository([], new VaultTokenDefinition());
        /** @var StaticEntityRepository<CustomerCollection> $customerRepository */
        $customerRepository = new StaticEntityRepository([], new CustomerDefinition());
        /** @var StaticEntityRepository<SubscriptionCollection> $subscriptionRepository */
        $subscriptionRepository = new StaticEntityRepository([]);

        $vaultTokenService = new VaultTokenService(
            $vaultTokenRepository,
            $customerRepository,
            $subscriptionRepository,
        );

        static::assertNull($vaultTokenService->getSubscriptions(
            new PaymentTransactionStruct(Uuid::randomHex()),
        ));
    }

    public function testGetSubscriptionOfUnknownType(): void
    {
        /** @var StaticEntityRepository<VaultTokenCollection> $vaultTokenRepository */
        $vaultTokenRepository = new StaticEntityRepository([], new VaultTokenDefinition());
        /** @var StaticEntityRepository<CustomerCollection> $customerRepository */
        $customerRepository = new StaticEntityRepository([], new CustomerDefinition());
        /** @var StaticEntityRepository<SubscriptionCollection> $subscriptionRepository */
        $subscriptionRepository = new StaticEntityRepository([]);

        $vaultTokenService = new VaultTokenService(
            $vaultTokenRepository,
            $customerRepository,
            $subscriptionRepository,
        );

        $this->expectException(SubscriptionTypeNotSupportedException::class);
        $vaultTokenService->getSubscriptions(new PaymentTransactionStruct(Uuid::randomHex(), recurring: new RecurringDataStruct(Uuid::randomHex(), new \DateTime())));
    }

    public function testSaveTokenToCustomer(): void
    {
        /** @var StaticEntityRepository<VaultTokenCollection> $vaultTokenRepository */
        $vaultTokenRepository = new StaticEntityRepository([[]], new VaultTokenDefinition());
        /** @var StaticEntityRepository<CustomerCollection> $customerRepository */
        $customerRepository = new StaticEntityRepository([], new CustomerDefinition());
        /** @var StaticEntityRepository<SubscriptionCollection> $subscriptionRepository */
        $subscriptionRepository = new StaticEntityRepository([]);

        $vaultTokenService = new VaultTokenService(
            $vaultTokenRepository,
            $customerRepository,
            $subscriptionRepository,
        );

        $transaction = new OrderTransactionEntity();
        $transaction->setId(Uuid::randomHex());
        $transaction->setPaymentMethodId('payment-method-id');

        $order = new OrderEntity();
        $order->setId(Uuid::randomHex());
        $orderCustomer = new OrderCustomerEntity();
        $orderCustomer->setCustomerId('customer-id');
        $order->setOrderCustomer($orderCustomer);

        $context = Context::createDefaultContext();
        $customerId = 'customer-id';
        $vault = (new Vault())->assign(['id' => 'vault-id', 'customer' => ['id' => 'customer-id']]);
        $attributes = new Attributes();
        $attributes->setVault($vault);
        $paymentSource = new Paypal();
        $paymentSource->setEmailAddress('test@hatoken.de');
        $paymentSource->setAttributes($attributes);
        $vaultTokenService->saveToken(new PaymentTransactionStruct($transaction->getId()), $transaction, $paymentSource, $customerId, $context);

        static::assertArrayHasKey('id', $vaultTokenRepository->upserts[0][0]);
        static::assertSame($vaultTokenRepository->upserts[0][0]['token'], 'vault-id');
        static::assertSame($vaultTokenRepository->upserts[0][0]['tokenCustomer'], 'customer-id');
        static::assertSame($vaultTokenRepository->upserts[0][0]['paymentMethodId'], $transaction->getPaymentMethodId());
        static::assertSame($vaultTokenRepository->upserts[0][0]['identifier'], $paymentSource->getVaultIdentifier());
        static::assertSame($vaultTokenRepository->upserts[0][0]['customerId'], $customerId);

        static::assertSame([
            'id' => $customerId,
            CustomerExtension::CUSTOMER_VAULT_TOKEN_MAPPING_EXTENSION => [[
                'customerId' => $customerId,
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

        /** @var StaticEntityRepository<VaultTokenCollection> $vaultTokenRepository */
        $vaultTokenRepository = new StaticEntityRepository([[]], new VaultTokenDefinition());
        /** @var StaticEntityRepository<CustomerCollection> $customerRepository */
        $customerRepository = new StaticEntityRepository([], new CustomerDefinition());
        /** @var StaticEntityRepository<SubscriptionCollection> $subscriptionRepository */
        $subscriptionRepository = new StaticEntityRepository([], new SubscriptionDefinition());

        $vaultTokenService = new VaultTokenService(
            $vaultTokenRepository,
            $customerRepository,
            $subscriptionRepository,
        );

        $transaction = new OrderTransactionEntity();
        $transaction->setId(Uuid::randomHex());
        $transaction->setPaymentMethodId('payment-method-id');

        $subscription = new SubscriptionEntity();
        $subscription->setId(Uuid::randomHex());
        $subscription->setNextSchedule(new \DateTime());

        $context = Context::createDefaultContext();
        $customerId = 'customer-id';
        $vault = (new Vault())->assign(['id' => 'vault-id', 'customer' => ['id' => 'customer-id']]);
        $attributes = new Attributes();
        $attributes->setVault($vault);
        $paymentSource = new Paypal();
        $paymentSource->setEmailAddress('test@hatoken.de');
        $paymentSource->setAttributes($attributes);

        /** @deprecated tag:v11.0.0 - Condition will always be true */
        if (\class_exists(SubscriptionsRecurringDataStruct::class)) {
            $recurring = new SubscriptionsRecurringDataStruct(new SubscriptionCollection([$subscription]));
        } else {
            $recurring = new SubscriptionRecurringDataStruct($subscription);
        }

        $vaultTokenService->saveToken(
            new PaymentTransactionStruct($transaction->getId(), recurring: $recurring),
            $transaction,
            $paymentSource,
            $customerId,
            $context,
        );

        static::assertSame($vaultTokenRepository->upserts[0][0]['token'], 'vault-id');
        static::assertSame($vaultTokenRepository->upserts[0][0]['tokenCustomer'], 'customer-id');
        static::assertSame($vaultTokenRepository->upserts[0][0]['paymentMethodId'], $transaction->getPaymentMethodId());
        static::assertSame($vaultTokenRepository->upserts[0][0]['identifier'], $paymentSource->getVaultIdentifier());
        static::assertSame($vaultTokenRepository->upserts[0][0]['customerId'], $customerId);

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

        /** @var StaticEntityRepository<VaultTokenCollection> $vaultTokenRepository */
        $vaultTokenRepository = new StaticEntityRepository([[]], new VaultTokenDefinition());
        /** @var StaticEntityRepository<CustomerCollection> $customerRepository */
        $customerRepository = new StaticEntityRepository([], new CustomerDefinition());

        $vaultTokenService = new VaultTokenService(
            $vaultTokenRepository,
            $customerRepository,
            null,
        );

        $transaction = new OrderTransactionEntity();
        $transaction->setId(Uuid::randomHex());
        $transaction->setPaymentMethodId('payment-method-id');

        $subscription = new SubscriptionEntity();
        $subscription->setId(Uuid::randomHex());
        $subscription->setNextSchedule(new \DateTime());

        $context = Context::createDefaultContext();
        $customerId = 'customer-id';
        $vault = new Vault();
        $vault->setId('vault-id');
        $attributes = new Attributes();
        $attributes->setVault($vault);
        $paymentSource = new Paypal();
        $paymentSource->setEmailAddress('test@hatoken.de');
        $paymentSource->setAttributes($attributes);

        /** @deprecated tag:v11.0.0 - Condition will always be true */
        if (\class_exists(SubscriptionsRecurringDataStruct::class)) {
            $recurring = new SubscriptionsRecurringDataStruct(new SubscriptionCollection([$subscription]));
        } else {
            $recurring = new SubscriptionRecurringDataStruct($subscription);
        }

        $this->expectException(ServiceNotFoundException::class);
        $vaultTokenService->saveToken(
            new PaymentTransactionStruct($transaction->getId(), recurring: $recurring),
            $transaction,
            $paymentSource,
            $customerId,
            $context,
        );
    }

    /**
     * @param array<string, mixed> $args
     */
    #[DataProvider('shouldRequestVaultingDataProvider')]
    public function testShouldRequestVaulting(array $args, bool $expected): void
    {
        if (!\class_exists(SubscriptionDefinition::class)) {
            static::markTestSkipped('Commercial is not available');
        }

        /** @var StaticEntityRepository<VaultTokenCollection> $vaultTokenRepository */
        $vaultTokenRepository = new StaticEntityRepository([[]], new VaultTokenDefinition());
        /** @var StaticEntityRepository<CustomerCollection> $customerRepository */
        $customerRepository = new StaticEntityRepository([], new CustomerDefinition());

        $vaultTokenService = new VaultTokenService(
            $vaultTokenRepository,
            $customerRepository,
            null,
        );

        $result = $vaultTokenService->shouldRequestVaulting(...$args);

        static::assertSame($expected, $result);
    }

    public static function shouldRequestVaultingDataProvider(): \Generator
    {
        yield 'empty' => [[], false];

        if (!\class_exists(SubscriptionDefinition::class)) {
            return;
        }

        $salesChannelContext = Generator::generateSalesChannelContext();
        yield 'non subscription context' => [['context' => clone $salesChannelContext], false];

        $salesChannelContext->addExtension('subscription', new ArrayStruct());
        yield 'subscription context' => [['context' => clone $salesChannelContext], true];

        if (\class_exists(PlanIntervalMappingStruct::class)) {
            $salesChannelContext->removeExtension('subscription');
            /** @var PlanIntervalMappingStruct<SalesChannelContext> $managedContexts */
            $managedContexts = new PlanIntervalMappingStruct();
            $salesChannelContext->addExtension('subscriptionManagedContexts', $managedContexts);
            yield 'empty managed subscription context' => [['context' => clone $salesChannelContext], false];

            $managedContexts->set('id', 'id', $salesChannelContext);
            yield 'managed subscription context' => [['context' => $salesChannelContext], true];
        }

        $bag = new RequestDataBag();
        yield 'empty parameter bag' => [['bag' => clone $bag], false];

        $bag->set(VaultTokenService::REQUEST_CREATE_VAULT, true);
        yield 'parameter bag' => [['bag' => clone $bag], true];

        $subscription = new SubscriptionEntity();
        $subscription->setId('subscription-id');
        $subscription->setNextSchedule(new \DateTimeImmutable());

        /** @deprecated tag:v11.0.0 - Condition will always be true */
        if (\class_exists(SubscriptionsRecurringDataStruct::class)) {
            yield 'payment transaction struct' => [
                ['paymentTransaction' => new PaymentTransactionStruct('id', recurring: new SubscriptionsRecurringDataStruct(new SubscriptionCollection([$subscription])))],
                true,
            ];
        }

        /** @deprecated tag:v11.0.0 - Will be removed in v6.8.0 */
        yield 'payment transaction struct deprecated' => [
            ['paymentTransaction' => new PaymentTransactionStruct('id', recurring: new SubscriptionRecurringDataStruct($subscription))],
            true,
        ];
    }
}
