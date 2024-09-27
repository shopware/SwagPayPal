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
use Swag\PayPal\Util\Lifecycle\Method\PayLaterMethodData;
use Swag\PayPal\Util\Lifecycle\Method\PaymentMethodDataRegistry;
use Swag\PayPal\Util\Lifecycle\Method\PayPalMethodData;

#[Package('checkout')]
class MerchantIntegrationsService
{
    /**
     * @internal
     */
    public function __construct(
        private readonly MerchantIntegrationsResourceInterface $merchantIntegrationsResource,
        private readonly CredentialsUtilInterface $credentialsUtil,
        private readonly PaymentMethodDataRegistry $paymentMethodDataRegistry,
        private readonly PayPalClientFactoryInterface $payPalClientFactory,
    ) {
    }

    public function getMerchantInformation(Context $context, ?string $salesChannelId = null): MerchantInformationStruct
    {
        $information = new MerchantInformationStruct();

        $integrations = $this->getIntegrations($salesChannelId);
        $information->setMerchantIntegrations($integrations);
        $information->setCapabilities($this->enrichCapabilities($integrations, $context, $salesChannelId));

        return $information;
    }

    private function getIntegrations(?string $salesChannelId = null): ?MerchantIntegrations
    {
        $merchantPayerId = $this->credentialsUtil->getMerchantPayerId($salesChannelId);

        if (!$merchantPayerId) {
            return null;
        }

        try {
            return $this->merchantIntegrationsResource->get(
                $merchantPayerId,
                $salesChannelId,
                $this->credentialsUtil->isSandbox($salesChannelId)
            );
        } catch (PayPalApiException|PayPalSettingsInvalidException) {
            // just catch exceptions thrown in case of invalid credentials
            return null;
        }
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

            if ($integrations !== null) {
                $capabilities[$paymentMethodId] = $methodData->validateCapability($integrations);

                continue;
            }

            if ($methodData instanceof PayPalMethodData || $methodData instanceof PayLaterMethodData) {
                try {
                    // if the PayPal client can be created, at least PayPal Wallet is active
                    $this->payPalClientFactory->getPayPalClient($salesChannelId);
                    $capabilities[$paymentMethodId] = AbstractMethodData::CAPABILITY_ACTIVE;

                    continue;
                } catch (\Throwable $e) {
                }
            }

            $capabilities[$paymentMethodId] = AbstractMethodData::CAPABILITY_INACTIVE;
        }

        return $capabilities;
    }
}
