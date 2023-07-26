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
use Shopware\Core\Framework\DataAbstractionLayer\Exception\EntityNotFoundException;
use Shopware\Core\Framework\DataAbstractionLayer\Search\Criteria;
use Shopware\Core\Framework\DataAbstractionLayer\Search\Filter\EqualsFilter;
use Shopware\Core\Framework\Log\Package;
use Shopware\Core\System\SalesChannel\SalesChannelContext;
use Swag\PayPal\Checkout\Exception\SubscriptionTypeNotSupportedException;
use Swag\PayPal\DataAbstractionLayer\Extension\CustomerExtension;
use Swag\PayPal\DataAbstractionLayer\VaultToken\VaultTokenDefinition;
use Swag\PayPal\DataAbstractionLayer\VaultToken\VaultTokenEntity;
use Swag\PayPal\RestApi\V2\Api\Order\PaymentSource\Common\Attributes;
use Swag\PayPal\RestApi\V2\Api\Order\PaymentSource\Common\Attributes\Vault;
use Swag\PayPal\RestApi\V2\Api\Order\PaymentSource\Token;
use Swag\PayPal\RestApi\V2\Api\Order\PaymentSource\VaultablePaymentSourceInterface;
use Symfony\Component\DependencyInjection\Exception\ServiceNotFoundException;

#[Package('checkout')]
class VaultTokenService
{
    public const CUSTOM_FIELD_PAYPAL_WALLET_VAULT = 'swagPaypalVaultToken_';
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

        $criteria = new Criteria();
        $criteria->addFilter(new EqualsFilter('customerId', $customerId));
        $criteria->addFilter(new EqualsFilter('paymentMethodId', $struct->getOrderTransaction()->getPaymentMethodId()));

        if ($subscription = $this->getSubscription($struct)) {
            // try to get the token from the subscription
            $tokenId = ($subscription->getCustomFields() ?? [])[self::CUSTOM_FIELD_PAYPAL_WALLET_VAULT] ?? null;

            if ($tokenId) {
                $criteria->setIds([$tokenId]);
            }
        } else {
            $criteria->addFilter(new EqualsFilter('mainMapping.customerId', $customerId));
        }

        /** @var VaultTokenEntity|null $token */
        $token = $this->vaultTokenRepository->search($criteria, $context)->first();

        return $token;
    }

    public function saveToken(SyncPaymentTransactionStruct $struct, VaultablePaymentSourceInterface $paymentSource, SalesChannelContext $context): void
    {
        $token = $paymentSource->getAttributes()?->getVault();
        if (!$token || !$token->getId()) {
            return;
        }

        $tokenId = $this->findTokenId($token->getId(), $context);
        if (!$tokenId) {
            $event = $this->vaultTokenRepository->upsert([
                [
                    'token' => $token->getId(),
                    'paymentMethodId' => $struct->getOrderTransaction()->getPaymentMethodId(),
                    'identifier' => $paymentSource->getVaultIdentifier(),
                    'customerId' => $context->getCustomerId(),
                ],
            ], $context->getContext());

            $tokenId = $event->getPrimaryKeys(VaultTokenDefinition::ENTITY_NAME)[0] ?? $this->findTokenId($token->getId(), $context);
            if (!$tokenId) {
                throw new EntityNotFoundException(VaultTokenDefinition::ENTITY_NAME, $token->getId());
            }
        }

        $customerId = $struct->getOrder()->getOrderCustomer()?->getCustomerId();
        if ($customerId !== null) {
            $this->saveTokenToCustomer($tokenId, $struct->getOrderTransaction()->getPaymentMethodId(), $context);
        }

        if ($subscription = $this->getSubscription($struct)) {
            $this->saveTokenToSubscription($subscription, $tokenId, $context->getContext());
        }
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

    private function saveTokenToSubscription(SubscriptionEntity $subscription, string $tokenId, Context $context): void
    {
        if ($this->subscriptionRepository === null) {
            throw new ServiceNotFoundException('subscription.repository');
        }

        $this->subscriptionRepository->upsert([[
            'id' => $subscription->getId(),
            'customFields' => [
                self::CUSTOM_FIELD_PAYPAL_WALLET_VAULT => $tokenId,
            ],
        ]], $context);
    }

    private function saveTokenToCustomer(string $tokenId, string $paymentMethodId, SalesChannelContext $context): void
    {
        $this->customerRepository->upsert([[
            'id' => $context->getCustomerId(),
            CustomerExtension::CUSTOMER_VAULT_TOKEN_MAPPING_EXTENSION => [[
                'customerId' => $context->getCustomerId(),
                'paymentMethodId' => $paymentMethodId,
                'tokenId' => $tokenId,
            ]],
        ]], $context->getContext());
    }

    private function findTokenId(string $token, SalesChannelContext $context): ?string
    {
        $criteria = new Criteria();
        $criteria->addFilter(new EqualsFilter('token', $token));
        $criteria->addFilter(new EqualsFilter('customerId', $context->getCustomerId()));
        $tokenId = $this->vaultTokenRepository->searchIds($criteria, $context->getContext())->firstId();

        if (!$tokenId) {
            return null;
        }

        return $tokenId;
    }
}
