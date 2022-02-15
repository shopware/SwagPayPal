<?php declare(strict_types=1);
/*
 * (c) shopware AG <info@shopware.com>
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Swag\PayPal\Setting\Service;

use Shopware\Core\Framework\Context;
use Shopware\Core\System\SystemConfig\SystemConfigService;
use Swag\PayPal\RestApi\V1\Api\MerchantIntegrations;
use Swag\PayPal\RestApi\V1\Resource\MerchantIntegrationsResource;
use Swag\PayPal\Setting\Settings;
use Swag\PayPal\Util\Lifecycle\Method\AbstractMethodData;
use Swag\PayPal\Util\Lifecycle\Method\PaymentMethodDataRegistry;

class MerchantIntegrationsService implements MerchantIntegrationsServiceInterface
{
    private MerchantIntegrationsResource $merchantIntegrationsResource;

    private SystemConfigService $systemConfigService;

    private PaymentMethodDataRegistry $paymentMethodDataRegistry;

    public function __construct(
        MerchantIntegrationsResource $merchantIntegrationsResource,
        SystemConfigService $systemConfigService,
        PaymentMethodDataRegistry $paymentMethodDataRegistry
    ) {
        $this->merchantIntegrationsResource = $merchantIntegrationsResource;
        $this->systemConfigService = $systemConfigService;
        $this->paymentMethodDataRegistry = $paymentMethodDataRegistry;
    }

    public function fetchMerchantIntegrations(?string $salesChannelId = null, Context $context): array
    {
        $sandboxActive = $this->systemConfigService->getBool(Settings::SANDBOX, $salesChannelId);
        $merchantId = $this->systemConfigService->getString($sandboxActive ? Settings::MERCHANT_PAYER_ID_SANDBOX : Settings::MERCHANT_PAYER_ID, $salesChannelId);

        try {
            $integrations = $this->merchantIntegrationsResource->get($merchantId, $salesChannelId, $sandboxActive);
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
