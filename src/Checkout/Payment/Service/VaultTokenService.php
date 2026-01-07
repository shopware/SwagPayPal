<?php declare(strict_types=1);
/*
 * (c) shopware AG <info@shopware.com>
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Swag\PayPal\Checkout\Payment\Service;

use Shopware\Commercial\Subscription\Checkout\Cart\Recurring\SubscriptionRecurringDataStruct;
use Shopware\Commercial\Subscription\Checkout\Cart\Recurring\SubscriptionsRecurringDataStruct;
use Shopware\Commercial\Subscription\Entity\Subscription\SubscriptionCollection;
use Shopware\Commercial\Subscription\Entity\Subscription\SubscriptionEntity;
use Shopware\Commercial\Subscription\Framework\Struct\PlanIntervalMappingStruct;
use Shopware\Core\Checkout\Customer\CustomerCollection;
use Shopware\Core\Checkout\Order\Aggregate\OrderTransaction\OrderTransactionEntity;
use Shopware\Core\Checkout\Order\OrderEntity;
use Shopware\Core\Checkout\Payment\Cart\PaymentTransactionStruct;
use Shopware\Core\Framework\Context;
use Shopware\Core\Framework\DataAbstractionLayer\EntityRepository;
use Shopware\Core\Framework\DataAbstractionLayer\Search\Criteria;
use Shopware\Core\Framework\DataAbstractionLayer\Search\Filter\EqualsFilter;
use Shopware\Core\Framework\Log\Package;
use Shopware\Core\Framework\Uuid\Uuid;
use Shopware\Core\System\SalesChannel\SalesChannelContext;
use Shopware\PayPalSDK\Contract\Struct\V2\Order\PaymentSource\VaultablePaymentSourceInterface;
use Shopware\PayPalSDK\Struct\V2\Order\PaymentSource\Common\Attributes;
use Shopware\PayPalSDK\Struct\V2\Order\PaymentSource\Common\Attributes\Vault;
use Swag\PayPal\Checkout\Exception\SubscriptionTypeNotSupportedException;
use Swag\PayPal\DataAbstractionLayer\Extension\CustomerExtension;
use Swag\PayPal\DataAbstractionLayer\VaultToken\VaultTokenCollection;
use Swag\PayPal\DataAbstractionLayer\VaultToken\VaultTokenEntity;
use Symfony\Component\DependencyInjection\Exception\ServiceNotFoundException;
use Symfony\Component\HttpFoundation\ParameterBag;

#[Package('checkout')]
class VaultTokenService
{
    public const CUSTOM_FIELD_SUBSCRIPTION_VAULT = 'swagPaypalVaultToken_%s';
    public const REQUEST_CREATE_VAULT = 'createVault';

    /**
     * @internal
     *
     * @param EntityRepository<VaultTokenCollection> $vaultTokenRepository
     * @param EntityRepository<CustomerCollection> $customerRepository
     * @param EntityRepository<SubscriptionCollection>|null $subscriptionRepository
     */
    public function __construct(
        private readonly EntityRepository $vaultTokenRepository,
        private readonly EntityRepository $customerRepository,
        private readonly ?EntityRepository $subscriptionRepository,
    ) {
    }

    public function getAvailableToken(PaymentTransactionStruct $struct, OrderTransactionEntity $orderTransaction, OrderEntity $order, Context $context): ?VaultTokenEntity
    {
        $customerId = $order->getOrderCustomer()?->getCustomerId();
        if (!$customerId) {
            return null;
        }

        $criteria = new Criteria();
        $criteria->setLimit(1);
        $criteria->addFilter(new EqualsFilter('customerId', $customerId));
        $criteria->addFilter(new EqualsFilter('paymentMethodId', $orderTransaction->getPaymentMethodId()));

        if ($subscriptions = $this->getSubscriptions($struct)) {
            $customFieldKey = $this->getSubscriptionCustomFieldKey($orderTransaction->getPaymentMethodId());
            $tokenIds = \array_unique(\array_values(
                $subscriptions->fmap(fn (SubscriptionEntity $subscription): string => (string) $subscription->getCustomFieldsValue($customFieldKey))
            ));

            if (\count($tokenIds) > 1) {
                throw new SubscriptionTypeNotSupportedException('recurring data struct containing multiple subscriptions');
            }

            if ($tokenIds) {
                $criteria->setIds($tokenIds);
            }
        }

        if (!$criteria->getIds()) {
            $criteria->addFilter(new EqualsFilter('mainMapping.customerId', $customerId));
        }

        return $this->vaultTokenRepository->search($criteria, $context)->getEntities()->first();
    }

    public function saveToken(PaymentTransactionStruct $struct, OrderTransactionEntity $orderTransaction, VaultablePaymentSourceInterface $paymentSource, string $customerId, Context $context): void
    {
        $token = $paymentSource->getAttributes()?->getVault();
        if (!$token || !$token->getId()) {
            return;
        }

        $tokenId = $this->findTokenId($token->getId(), $customerId, $context);
        if (!$tokenId) {
            $tokenId = Uuid::randomHex();
            $this->vaultTokenRepository->upsert([
                [
                    'id' => $tokenId,
                    'token' => $token->getId(),
                    'tokenCustomer' => $token->getCustomer()?->getId(),
                    'paymentMethodId' => $orderTransaction->getPaymentMethodId(),
                    'identifier' => $paymentSource->getVaultIdentifier(),
                    'customerId' => $customerId,
                ],
            ], $context);
        }

        $this->saveTokenToCustomer($tokenId, $orderTransaction->getPaymentMethodId(), $customerId, $context);

        if ($subscriptions = $this->getSubscriptions($struct)) {
            $this->saveTokenToSubscriptions($subscriptions, $tokenId, $orderTransaction->getPaymentMethodId(), $context);
        }
    }

    /**
     * @deprecated tag:v11.0.0 - Will be removed and is replaced by {@see self::getSubscriptions}
     */
    public function getSubscription(PaymentTransactionStruct $struct): ?SubscriptionEntity
    {
        return $this->getSubscriptions($struct)?->first();
    }

    public function getSubscriptions(PaymentTransactionStruct $struct): ?SubscriptionCollection
    {
        $recurring = $struct->getRecurring();
        if ($recurring === null) {
            return null;
        }

        if (\class_exists(SubscriptionsRecurringDataStruct::class) && $recurring instanceof SubscriptionsRecurringDataStruct) {
            return $recurring->getSubscriptions();
        }

        /** @deprecated tag:v11.0.0 - Will be removed with 6.8.0.0 */
        if ($recurring instanceof SubscriptionRecurringDataStruct) {
            return new SubscriptionCollection([$recurring->getSubscription()]);
        }

        throw new SubscriptionTypeNotSupportedException($recurring::class);
    }

    public function requestVaulting(VaultablePaymentSourceInterface $paymentSource): void
    {
        $vault = new Vault();
        $vault->setStoreInVault(Vault::STORE_IN_VAULT_ON_SUCCESS);
        $vault->setUsageType(Vault::USAGE_TYPE_MERCHANT);

        $attributes = new Attributes();
        $attributes->setVault($vault);

        $paymentSource->setAttributes($attributes);
    }

    /**
     * @internal
     */
    public function shouldRequestVaulting(
        ?SalesChannelContext $context = null,
        ?ParameterBag $bag = null,
        ?PaymentTransactionStruct $paymentTransaction = null,
    ): bool {
        if ($context && ($context->hasExtension('subscription') || (\class_exists(PlanIntervalMappingStruct::class) && $context->getExtensionOfType('subscriptionManagedContexts', PlanIntervalMappingStruct::class)?->all()))) {
            return true;
        }

        if ($bag && $bag->getBoolean(VaultTokenService::REQUEST_CREATE_VAULT)) {
            return true;
        }

        if ($paymentTransaction && $this->getSubscriptions($paymentTransaction)) {
            return true;
        }

        return false;
    }

    private function saveTokenToSubscriptions(SubscriptionCollection $subscriptions, string $tokenId, string $paymentMethodId, Context $context): void
    {
        if ($this->subscriptionRepository === null) {
            throw new ServiceNotFoundException('subscription.repository');
        }

        $this->subscriptionRepository->upsert(\array_values($subscriptions->map(fn (SubscriptionEntity $subscription) => [
            'id' => $subscription->getId(),
            'customFields' => [
                $this->getSubscriptionCustomFieldKey($paymentMethodId) => $tokenId,
            ],
        ])), $context);
    }

    private function saveTokenToCustomer(string $tokenId, string $paymentMethodId, string $customerId, Context $context): void
    {
        $this->customerRepository->upsert([[
            'id' => $customerId,
            CustomerExtension::CUSTOMER_VAULT_TOKEN_MAPPING_EXTENSION => [[
                'customerId' => $customerId,
                'paymentMethodId' => $paymentMethodId,
                'tokenId' => $tokenId,
            ]],
        ]], $context);
    }

    private function findTokenId(string $token, string $customerId, Context $context): ?string
    {
        $criteria = new Criteria();
        $criteria->addFilter(new EqualsFilter('token', $token));
        $criteria->addFilter(new EqualsFilter('customerId', $customerId));

        return $this->vaultTokenRepository->searchIds($criteria, $context)->firstId();
    }

    private function getSubscriptionCustomFieldKey(string $paymentMethodId): string
    {
        return \sprintf(self::CUSTOM_FIELD_SUBSCRIPTION_VAULT, $paymentMethodId);
    }
}
