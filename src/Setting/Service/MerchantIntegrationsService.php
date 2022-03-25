<?php declare(strict_types=1);
/*
 * (c) shopware AG <info@shopware.com>
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Swag\PayPal\Setting\Service;

use Shopware\Core\Framework\Context;
use Swag\PayPal\RestApi\V1\Api\MerchantIntegrations;
use Swag\PayPal\RestApi\V1\Resource\MerchantIntegrationsResourceInterface;
use Swag\PayPal\Util\Lifecycle\Method\AbstractMethodData;
use Swag\PayPal\Util\Lifecycle\Method\PaymentMethodDataRegistry;

class MerchantIntegrationsService implements MerchantIntegrationsServiceInterface
{
    private MerchantIntegrationsResourceInterface $merchantIntegrationsResource;

    private CredentialsUtilInterface $credentialsUtil;

    private PaymentMethodDataRegistry $paymentMethodDataRegistry;

    public function __construct(
        MerchantIntegrationsResourceInterface $merchantIntegrationsResource,
        CredentialsUtilInterface $credentialsUtil,
        PaymentMethodDataRegistry $paymentMethodDataRegistry
    ) {
        $this->merchantIntegrationsResource = $merchantIntegrationsResource;
        $this->credentialsUtil = $credentialsUtil;
        $this->paymentMethodDataRegistry = $paymentMethodDataRegistry;
    }

    public function fetchMerchantIntegrations(Context $context, ?string $salesChannelId = null): array
    {
        try {
            $integrations = $this->merchantIntegrationsResource->get(
                $this->credentialsUtil->getMerchantPayerId($salesChannelId),
                $salesChannelId,
                $this->credentialsUtil->isSandbox($salesChannelId)
            );
        } catch (\Throwable $e) {
            // just catch exceptions thrown in case of invalid credentials
            $integrations = null;
        }

        return $this->handleIntegrations($integrations, $context);
    }

    private function handleIntegrations(?MerchantIntegrations $integrations, Context $context): array
    {
        $methods = [];

        foreach ($this->paymentMethodDataRegistry->getPaymentMethods() as $methodData) {
            $paymentMethodId = $this->paymentMethodDataRegistry->getEntityIdFromData($methodData, $context);

            if ($paymentMethodId === null) {
                continue;
            }

            $methods[$paymentMethodId] = $integrations ? $methodData->validateCapability($integrations) : AbstractMethodData::CAPABILITY_INACTIVE;
        }

        return $methods;
    }
}
