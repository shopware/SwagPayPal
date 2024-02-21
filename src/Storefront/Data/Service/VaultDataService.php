<?php declare(strict_types=1);
/*
 * (c) shopware AG <info@shopware.com>
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Swag\PayPal\Storefront\Data\Service;

use Shopware\Core\Checkout\Customer\CustomerEntity;
use Shopware\Core\Framework\DataAbstractionLayer\EntityRepository;
use Shopware\Core\Framework\DataAbstractionLayer\Search\Criteria;
use Shopware\Core\Framework\DataAbstractionLayer\Search\Filter\EqualsFilter;
use Shopware\Core\Framework\Log\Package;
use Shopware\Core\System\SalesChannel\SalesChannelContext;
use Swag\PayPal\DataAbstractionLayer\VaultToken\VaultTokenEntity;
use Swag\PayPal\RestApi\V1\Resource\TokenResourceInterface;
use Swag\PayPal\Storefront\Data\Struct\VaultData;
use Swag\PayPal\Util\Lifecycle\Method\ACDCMethodData;
use Swag\PayPal\Util\Lifecycle\Method\PaymentMethodDataRegistry;

#[Package('checkout')]
class VaultDataService
{
    /**
     * @internal
     */
    public function __construct(
        private readonly EntityRepository $vaultRepository,
        private readonly PaymentMethodDataRegistry $paymentMethodDataRegistry,
        private readonly TokenResourceInterface $tokenResource,
    ) {
    }

    public function buildData(SalesChannelContext $context): ?VaultData
    {
        $customer = $context->getCustomer();
        if ($customer === null || $customer->getGuest() === true) {
            return null;
        }

        $paymentMethod = $this->paymentMethodDataRegistry->getPaymentMethodByHandler($context->getPaymentMethod()->getHandlerIdentifier());
        if ($paymentMethod === null) {
            return null;
        }

        $isVaultable = $paymentMethod->isVaultable($context);
        if (!$isVaultable) {
            return null;
        }

        $vault = $this->fetchVaultData($customer, $context);

        $struct = new VaultData();
        $struct->setIdentifier($vault ? $vault->getIdentifier() : null);

        if ($paymentMethod instanceof ACDCMethodData) {
            $struct->setSnippetType(VaultData::SNIPPET_TYPE_CARD);
        }

        return $struct;
    }

    public function getUserIdToken(SalesChannelContext $context): ?string
    {
        $customer = $context->getCustomer();
        if ($customer === null || $customer->getGuest() === true) {
            return null;
        }

        $vault = $this->fetchVaultData($customer, $context);
        if ($vault !== null) {
            return $this->tokenResource->getUserIdToken($context->getSalesChannelId(), $vault->getTokenCustomer())->getIdToken();
        }

        return $this->tokenResource->getUserIdToken($context->getSalesChannelId())->getIdToken();
    }

    private function fetchVaultData(CustomerEntity $customer, SalesChannelContext $context): ?VaultTokenEntity
    {
        $criteria = new Criteria();
        $criteria->addFilter(new EqualsFilter('mainMapping.customerId', $customer->getId()));
        $criteria->addFilter(new EqualsFilter('mainMapping.paymentMethodId', $context->getPaymentMethod()->getId()));

        /** @var VaultTokenEntity|null $vault */
        $vault = $this->vaultRepository->search($criteria, $context->getContext())->first();

        return $vault;
    }
}
