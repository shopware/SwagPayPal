<?php declare(strict_types=1);
/*
 * (c) shopware AG <info@shopware.com>
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Swag\PayPal\Storefront\Data\Service;

use Shopware\Core\Framework\DataAbstractionLayer\EntityRepository;
use Shopware\Core\Framework\DataAbstractionLayer\Search\Criteria;
use Shopware\Core\Framework\DataAbstractionLayer\Search\Filter\EqualsFilter;
use Shopware\Core\Framework\Log\Package;
use Shopware\Core\System\SalesChannel\SalesChannelContext;
use Shopware\Core\System\SystemConfig\SystemConfigService;
use Swag\PayPal\DataAbstractionLayer\VaultToken\VaultTokenEntity;
use Swag\PayPal\Setting\Settings;
use Swag\PayPal\Storefront\Data\Struct\VaultData;
use Swag\PayPal\Util\Lifecycle\Method\PaymentMethodDataRegistry;

#[Package('checkout')]
class VaultDataService
{
    /**
     * @internal
     */
    public function __construct(
        private EntityRepository $vaultRepository,
        private SystemConfigService $systemConfigService,
        private PaymentMethodDataRegistry $paymentMethodDataRegistry,
    ) {
    }

    public function buildData(SalesChannelContext $context): ?VaultData
    {
        $customer = $context->getCustomer();
        if ($customer === null || $customer->getGuest() === true) {
            return null;
        }

        $isVaultable = $this->paymentMethodDataRegistry->getPaymentMethodByHandler($context->getPaymentMethod()->getHandlerIdentifier())?->isVaultable();
        if (!$isVaultable) {
            return null;
        }

        $criteria = new Criteria();
        $criteria->addFilter(new EqualsFilter('mainMapping.customerId', $customer->getId()));
        $criteria->addFilter(new EqualsFilter('mainMapping.paymentMethodId', $context->getPaymentMethod()->getId()));

        /** @var VaultTokenEntity|null $vault */
        $vault = $this->vaultRepository->search($criteria, $context->getContext())->first();

        $struct = new VaultData();
        $struct->setIdentifier($vault ? $vault->getIdentifier() : null);
        $struct->setPreselect($this->systemConfigService->getBool(Settings::VAULTING_ENABLE_ALWAYS, $context->getSalesChannelId()));

        return $struct;
    }
}
