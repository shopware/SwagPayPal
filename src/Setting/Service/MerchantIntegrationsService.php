<?php declare(strict_types=1);
/*
 * (c) shopware AG <info@shopware.com>
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Swag\PayPal\Setting\Service;

use Shopware\Core\Framework\Context;
use Swag\PayPal\RestApi\Client\PayPalClientFactoryInterface;
use Swag\PayPal\RestApi\V1\Api\MerchantIntegrations;
use Swag\PayPal\RestApi\V1\Resource\MerchantIntegrationsResourceInterface;
use Swag\PayPal\Util\Lifecycle\Method\AbstractMethodData;
use Swag\PayPal\Util\Lifecycle\Method\PaymentMethodDataRegistry;
use Swag\PayPal\Util\Lifecycle\Method\PayPalMethodData;

class MerchantIntegrationsService implements MerchantIntegrationsServiceInterface
{
    private MerchantIntegrationsResourceInterface $merchantIntegrationsResource;

    private CredentialsUtilInterface $credentialsUtil;

    private PaymentMethodDataRegistry $paymentMethodDataRegistry;

    private PayPalClientFactoryInterface $payPalClientFactory;

    public function __construct(
        MerchantIntegrationsResourceInterface $merchantIntegrationsResource,
        CredentialsUtilInterface $credentialsUtil,
        PaymentMethodDataRegistry $paymentMethodDataRegistry,
        PayPalClientFactoryInterface $payPalClientFactory
    ) {
        $this->merchantIntegrationsResource = $merchantIntegrationsResource;
        $this->credentialsUtil = $credentialsUtil;
        $this->paymentMethodDataRegistry = $paymentMethodDataRegistry;
        $this->payPalClientFactory = $payPalClientFactory;
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

        $capabilities = $this->handleIntegrations($integrations, $context);

        if ($integrations !== null) {
            return $capabilities;
        }

        try {
            $this->payPalClientFactory->getPayPalClient($salesChannelId);

            $payPalPaymentMethodId = $this->paymentMethodDataRegistry->getEntityIdFromData(
                $this->paymentMethodDataRegistry->getPaymentMethod(PayPalMethodData::class),
                $context
            );

            if ($payPalPaymentMethodId === null) {
                return $capabilities;
            }

            $capabilities[$payPalPaymentMethodId] = AbstractMethodData::CAPABILITY_ACTIVE;
        } catch (\Throwable $e) {
        }

        return $capabilities;
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
