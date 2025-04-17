<?php declare(strict_types=1);
/*
 * (c) shopware AG <info@shopware.com>
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Swag\PayPal\Setting\Service;

use Shopware\Core\Framework\Log\Package;
use Shopware\PayPalSDK\Context\ApiContext;
use Shopware\PayPalSDK\Context\AuthorizationCodeOAuthContext;
use Shopware\PayPalSDK\Context\CredentialsOAuthContext;
use Shopware\PayPalSDK\Contract\Gateway\TokenGatewayInterface;
use Shopware\PayPalSDK\Gateway\CustomerGateway;
use Shopware\PayPalSDK\Struct\V1\MerchantIntegrations\Credentials;
use Swag\PayPal\RestApi\Exception\PayPalApiException;
use Swag\PayPal\RestApi\PartnerAttributionId;
use Swag\PayPal\RestApi\PartnerId;

#[Package('checkout')]
class ApiCredentialService
{
    /**
     * @internal
     */
    public function __construct(
        private readonly TokenGatewayInterface $tokenGateway,
        private readonly CustomerGateway $customerGateway,
    ) {
    }

    /**
     * @throws PayPalApiException
     */
    public function testApiCredentials(string $clientId, string $clientSecret, bool $sandboxActive, ?string $merchantPayerId): bool
    {
        $context = (new ApiContext(
            new CredentialsOAuthContext($clientId, $clientSecret),
            $sandboxActive,
        ))->withPartnerAttributionId(PartnerAttributionId::PAYPAL_PPCP);

        if ($merchantPayerId) {
            $this->customerGateway->getMerchantIntegrations($sandboxActive ? PartnerId::SANDBOX : PartnerId::LIVE, $merchantPayerId, $context);
        } else {
            $this->tokenGateway->getToken($context);
        }

        return true;
    }

    public function getApiCredentials(string $authCode, string $sharedId, string $nonce, bool $sandboxActive): Credentials
    {
        $context = (new ApiContext(
            new AuthorizationCodeOAuthContext($authCode, $sharedId, $nonce),
            $sandboxActive,
        ))->withPartnerAttributionId(PartnerAttributionId::PAYPAL_PPCP);

        return $this->customerGateway->getCredentials($sandboxActive ? PartnerId::SANDBOX : PartnerId::LIVE, $context);
    }
}
