<?php declare(strict_types=1);
/*
 * (c) shopware AG <info@shopware.com>
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Swag\PayPal\RestApi\V1\Resource;

use Shopware\Core\Framework\Log\Package;
use Shopware\PayPalSDK\Gateway\CustomerGateway;
use Shopware\PayPalSDK\Struct\V1\MerchantIntegrations;
use Swag\PayPal\RestApi\ApiContextFactoryInterface;
use Swag\PayPal\RestApi\Exception\PayPalApiException;
use Swag\PayPal\RestApi\PartnerId;

#[Package('checkout')]
class MerchantIntegrationsResource implements MerchantIntegrationsResourceInterface
{
    /**
     * @internal
     */
    public function __construct(
        private readonly CustomerGateway $customerGateway,
        private readonly ApiContextFactoryInterface $apiContextFactory,
    ) {
    }

    /**
     * @throws PayPalApiException
     */
    public function get(string $merchantId, ?string $salesChannelId = null, bool $sandboxActive = true): MerchantIntegrations
    {
        $context = $this->apiContextFactory
            ->getApiContext($salesChannelId)
            ->withSandbox($sandboxActive);

        return $this->customerGateway->getMerchantIntegrations($context->isSandbox() ? PartnerId::SANDBOX : PartnerId::LIVE, $merchantId, $context);
    }
}
