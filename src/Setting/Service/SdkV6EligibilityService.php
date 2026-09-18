<?php declare(strict_types=1);
/*
 * (c) shopware AG <info@shopware.com>
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Swag\PayPal\Setting\Service;

use Psr\Log\LoggerInterface;
use Shopware\Core\Framework\Log\Package;
use Shopware\PayPalSDK\Context\CredentialsOAuthContext;
use Shopware\PayPalSDK\Contract\Gateway\TokenGatewayInterface;
use Swag\PayPal\RestApi\ApiContextFactoryInterface;
use Swag\PayPal\RestApi\Context\UncachedOAuthContext;

/**
 * The PayPal web SDK v6 has to be activated by the merchant in their PayPal account.
 * Whether that happened is expressed by the scopes granted to the client token of the merchant.
 */
#[Package('checkout')]
class SdkV6EligibilityService
{
    public const SCOPE_CLIENT_PAYMENTS_ELIGIBILITY = 'https://uri.paypal.com/services/payments/client-payments-eligibility';

    /**
     * @internal
     */
    public function __construct(
        private readonly TokenGatewayInterface $tokenGateway,
        private readonly ApiContextFactoryInterface $apiContextFactory,
        private readonly LoggerInterface $logger,
    ) {
    }

    /**
     * @return bool|null null if the eligibility could not be determined, e.g. because PayPal could not be reached
     */
    public function isEligible(?string $salesChannelId = null): ?bool
    {
        $scope = $this->getClientTokenScopes($salesChannelId);

        if ($scope === null) {
            return null;
        }

        return \str_contains($scope, self::SCOPE_CLIENT_PAYMENTS_ELIGIBILITY);
    }

    private function getClientTokenScopes(?string $salesChannelId): ?string
    {
        try {
            $context = $this->apiContextFactory->getApiContext($salesChannelId);

            if (!($oauthContext = $context->getOAuthContext()) instanceof CredentialsOAuthContext) {
                throw new \InvalidArgumentException($this->apiContextFactory::class . ' should have returned a context including ' . CredentialsOAuthContext::class);
            }

            // a cached token still carries the scopes granted when it was issued,
            // so a merchant that just activated the SDK v6 would keep being reported as ineligible
            $clientTokenContext = new UncachedOAuthContext($oauthContext->intoClientTokenContext());

            $token = $this->tokenGateway->getToken($context->withOAuthContext($clientTokenContext));
        } catch (\Throwable $e) {
            $this->logger->info('Could not determine the PayPal SDK v6 eligibility: {message}', ['message' => $e->getMessage(), 'salesChannelId' => $salesChannelId]);

            return null;
        }

        if (!$token->isset('scope')) {
            return null;
        }

        return $token->getScope();
    }
}
