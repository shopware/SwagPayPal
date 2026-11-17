<?php declare(strict_types=1);
/*
 * (c) shopware AG <info@shopware.com>
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Swag\PayPal\Checkout\Payment\Service;

use Shopware\Commercial\Subscription\Checkout\Cart\Recurring\SubscriptionRecurringDataStruct;
use Shopware\Commercial\Subscription\Entity\Subscription\SubscriptionEntity;
use Shopware\Core\Checkout\Payment\Cart\SyncPaymentTransactionStruct;
use Shopware\Core\Framework\Context;
use Shopware\Core\Framework\DataAbstractionLayer\EntityRepository;
use Shopware\Core\Framework\DataAbstractionLayer\Search\Criteria;
use Shopware\Core\Framework\DataAbstractionLayer\Search\Filter\EqualsFilter;
use Shopware\Core\Framework\Log\Package;
use Shopware\Core\Framework\Uuid\Uuid;
use Swag\PayPal\Checkout\Exception\SubscriptionTypeNotSupportedException;
use Swag\PayPal\DataAbstractionLayer\Extension\CustomerExtension;
use Swag\PayPal\DataAbstractionLayer\VaultToken\VaultTokenEntity;
use Swag\PayPal\RestApi\V2\Api\Order\PaymentSource\Common\Attributes;
use Swag\PayPal\RestApi\V2\Api\Order\PaymentSource\Common\Attributes\Vault;
use Swag\PayPal\RestApi\V2\Api\Order\PaymentSource\Token;
use Swag\PayPal\RestApi\V2\Api\Order\PaymentSource\VaultablePaymentSourceInterface;
use Swag\PayPal\SwagPayPal;
use Symfony\Component\DependencyInjection\Exception\ServiceNotFoundException;

#[Package('checkout')]
class VaultTokenService
{
    public const CUSTOM_FIELD_SUBSCRIPTION_VAULT = 'swagPaypalVaultToken_%s';
    public const REQUEST_CREATE_VAULT = 'createVault';

    /**
     * @internal
     */
    public function __construct(
        private readonly EntityRepository $vaultTokenRepository,
        private readonly EntityRepository $customerRepository,
        private readonly ?EntityRepository $subscriptionRepository,
    ) {
    }

    public function getAvailableToken(SyncPaymentTransactionStruct $struct, Context $context): ?VaultTokenEntity
    {
        $customerId = $struct->getOrder()->getOrderCustomer()?->getCustomerId();
        if (!$customerId) {
            return null;
        }

        $criteria = $this->createTokenCriteria($customerId, $struct->getOrderTransaction()->getPaymentMethodId());

        if ($subscription = $this->getSubscription($struct)) {
            // try to get the token from the subscription
            $tokenId = ($subscription->getCustomFields() ?? [])[$this->getSubscriptionCustomFieldKey($struct->getOrderTransaction()->getPaymentMethodId())] ?? null;

            if (\is_string($tokenId) && $tokenId !== '') {
                $criteria->setIds([$tokenId]);
            }
        }

        if (!$criteria->getIds()) {
            // the first order of a subscription has no token yet, so the customer's main token is used, like outside of subscriptions
            $criteria->addFilter(new EqualsFilter('mainMapping.customerId', $customerId));
        }

        /** @var VaultTokenEntity|null $token */
        $token = $this->vaultTokenRepository->search($criteria, $context)->first();

        return $token;
    }

    /**
     * Renewals keep using the token of their subscription. A payment of the customer, e.g. of a failed renewal, uses
     * the one they saved last for the payment method instead, as the storefront shows them that one, and their
     * subscription keeps it afterwards.
     *
     * @internal
     */
    public function getTokenForPayment(SyncPaymentTransactionStruct $struct, Context $context, bool $isRecurring): ?VaultTokenEntity
    {
        if ($isRecurring) {
            return $this->getAvailableToken($struct, $context);
        }

        $customerId = $struct->getOrder()->getOrderCustomer()?->getCustomerId();
        if (!$customerId) {
            return null;
        }

        $criteria = $this->createTokenCriteria($customerId, $struct->getOrderTransaction()->getPaymentMethodId());
        $criteria->addFilter(new EqualsFilter('mainMapping.customerId', $customerId));

        /** @var VaultTokenEntity|null $token */
        $token = $this->vaultTokenRepository->search($criteria, $context)->first();

        return $token;
    }

    public function saveToken(SyncPaymentTransactionStruct $struct, VaultablePaymentSourceInterface $paymentSource, string $customerId, Context $context): void
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
                    'paymentMethodId' => $struct->getOrderTransaction()->getPaymentMethodId(),
                    'identifier' => $paymentSource->getVaultIdentifier(),
                    'customerId' => $customerId,
                ],
            ], $context);
        }

        $this->saveTokenToCustomer($tokenId, $struct->getOrderTransaction()->getPaymentMethodId(), $customerId, $context);

        if ($subscription = $this->getSubscription($struct)) {
            $this->saveTokenToSubscription($subscription, $tokenId, $struct->getOrderTransaction()->getPaymentMethodId(), $context);
        }
    }

    /**
     * PayPal returns no vault for a payment with an already saved payment method, so this saves its token to the
     * subscription. Its renewals then keep using it, even after the customer removed it or saved another one.
     *
     * @internal
     */
    public function saveUsedTokenToSubscriptions(SyncPaymentTransactionStruct $struct, Context $context): void
    {
        // recorded when the PayPal order was created, as the customer's saved one may have changed since
        $tokenId = $struct->getOrderTransaction()->getCustomFieldsValue(SwagPayPal::ORDER_TRANSACTION_CUSTOM_FIELDS_PAYPAL_VAULT_TOKEN_ID);
        if (!\is_string($tokenId) || $tokenId === '') {
            return;
        }

        $subscription = $this->getSubscription($struct);
        if (!$subscription) {
            return;
        }

        $paymentMethodId = $struct->getOrderTransaction()->getPaymentMethodId();
        if ($subscription->getCustomFieldsValue($this->getSubscriptionCustomFieldKey($paymentMethodId)) === $tokenId) {
            return;
        }

        $this->saveTokenToSubscription($subscription, $tokenId, $paymentMethodId, $context);
    }

    public function getSubscription(SyncPaymentTransactionStruct $struct): ?SubscriptionEntity
    {
        $recurring = $struct->getRecurring();
        if ($recurring === null) {
            return null;
        }

        if (!$recurring instanceof SubscriptionRecurringDataStruct) {
            throw new SubscriptionTypeNotSupportedException($recurring::class);
        }

        return $recurring->getSubscription();
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

    private function saveTokenToSubscription(SubscriptionEntity $subscription, string $tokenId, string $paymentMethodId, Context $context): void
    {
        if ($this->subscriptionRepository === null) {
            throw new ServiceNotFoundException('subscription.repository');
        }

        $this->subscriptionRepository->upsert([[
            'id' => $subscription->getId(),
            'customFields' => [
                $this->getSubscriptionCustomFieldKey($paymentMethodId) => $tokenId,
            ],
        ]], $context);
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

    private function createTokenCriteria(string $customerId, string $paymentMethodId): Criteria
    {
        $criteria = new Criteria();
        $criteria->addFilter(new EqualsFilter('customerId', $customerId));
        $criteria->addFilter(new EqualsFilter('paymentMethodId', $paymentMethodId));

        return $criteria;
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
