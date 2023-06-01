<?php declare(strict_types=1);
/*
 * (c) shopware AG <info@shopware.com>
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Swag\PayPal\Setting\Service;

use Shopware\Core\Framework\Context;
use Shopware\Core\Framework\Log\Package;
use Swag\PayPal\RestApi\Client\PayPalClientFactoryInterface;
use Swag\PayPal\RestApi\Exception\PayPalApiException;
use Swag\PayPal\RestApi\V1\Api\MerchantIntegrations;
use Swag\PayPal\RestApi\V1\Resource\MerchantIntegrationsResourceInterface;
use Swag\PayPal\Setting\Exception\PayPalSettingsInvalidException;
use Swag\PayPal\Setting\Struct\MerchantInformationStruct;
use Swag\PayPal\Util\Lifecycle\Method\AbstractMethodData;
use Swag\PayPal\Util\Lifecycle\Method\PaymentMethodDataRegistry;
use Swag\PayPal\Util\Lifecycle\Method\PayPalMethodData;

#[Package('checkout')]
class MerchantIntegrationsService
{
    private MerchantIntegrationsResourceInterface $merchantIntegrationsResource;

    private CredentialsUtilInterface $credentialsUtil;

    private PaymentMethodDataRegistry $paymentMethodDataRegistry;

    private PayPalClientFactoryInterface $payPalClientFactory;

    /**
     * @internal
     */
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

    public function getMerchantInformation(Context $context, ?string $salesChannelId = null): MerchantInformationStruct
    {
        $information = new MerchantInformationStruct();

        try {
            $integrations = $this->merchantIntegrationsResource->get(
                $this->credentialsUtil->getMerchantPayerId($salesChannelId),
                $salesChannelId,
                $this->credentialsUtil->isSandbox($salesChannelId)
            );

            $information->setMerchantIntegrations($integrations);
        } catch (PayPalApiException|PayPalSettingsInvalidException) {
            // just catch exceptions thrown in case of invalid credentials
        }

        $information->setCapabilities($this->enrichCapabilities($integrations ?? null, $context, $salesChannelId));

        return $information;
    }

    /**
     * @return array<string, string>
     */
    private function enrichCapabilities(?MerchantIntegrations $integrations, Context $context, ?string $salesChannelId = null): array
    {
        $capabilities = [];

        foreach ($this->paymentMethodDataRegistry->getPaymentMethods() as $methodData) {
            $paymentMethodId = $this->paymentMethodDataRegistry->getEntityIdFromData($methodData, $context);

            if ($paymentMethodId === null) {
                continue;
            }

            $capabilities[$paymentMethodId] = $integrations ? $methodData->validateCapability($integrations) : AbstractMethodData::CAPABILITY_INACTIVE;
        }

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
}
