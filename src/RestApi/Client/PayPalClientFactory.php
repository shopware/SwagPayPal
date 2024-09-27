<?php declare(strict_types=1);
/*
 * (c) shopware AG <info@shopware.com>
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Swag\PayPal\RestApi\Client;

use Psr\Log\LoggerInterface;
use Shopware\Core\Framework\Log\Package;
use Swag\PayPal\RestApi\PartnerAttributionId;
use Swag\PayPal\RestApi\V1\Resource\TokenResourceInterface;
use Swag\PayPal\RestApi\V1\Service\CredentialProviderInterface;
use Swag\PayPal\Setting\Service\CredentialsUtilInterface;

#[Package('checkout')]
class PayPalClientFactory implements PayPalClientFactoryInterface
{
    /**
     * @var PayPalClient[]
     */
    private array $payPalClients = [];

    /**
     * @internal
     */
    public function __construct(
        private readonly TokenResourceInterface $tokenResource,
        private readonly CredentialsUtilInterface $credentialsUtil,
        private readonly CredentialProviderInterface $credentialProvider,
        private readonly LoggerInterface $logger,
    ) {
    }

    public function getPayPalClient(
        ?string $salesChannelId,
        string $partnerAttributionId = PartnerAttributionId::PAYPAL_CLASSIC,
        bool $isFirstParty = false,
    ): PayPalClientInterface {
        if ($partnerAttributionId !== PartnerAttributionId::PAYPAL_PLUS && $this->credentialsUtil->getMerchantPayerId($salesChannelId)) {
            $partnerAttributionId = PartnerAttributionId::PAYPAL_PPCP;
        }

        $key = ($salesChannelId ?? 'null') . $partnerAttributionId . ($isFirstParty ? '1' : '0');

        $token = $this->tokenResource->getToken($salesChannelId);
        $headers = $this->credentialProvider->createAuthorizationHeaders($token, !$isFirstParty ? $this->credentialsUtil->getMerchantPayerId($salesChannelId) : null);

        if (!isset($this->payPalClients[$key])) {
            $this->payPalClients[$key] = new PayPalClient(
                $headers,
                $this->credentialsUtil->getBaseUrl($salesChannelId),
                $this->logger,
                $partnerAttributionId,
            );
        }

        return $this->payPalClients[$key];
    }
}
