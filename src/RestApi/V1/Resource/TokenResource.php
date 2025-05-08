<?php declare(strict_types=1);
/*
 * (c) shopware AG <info@shopware.com>
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Swag\PayPal\RestApi\V1\Resource;

use Shopware\Core\Framework\Log\Package;
use Shopware\PayPalSDK\Context\CredentialsOAuthContext;
use Shopware\PayPalSDK\Contract\Gateway\TokenGatewayInterface;
use Shopware\PayPalSDK\Exception\ApiException;
use Shopware\PayPalSDK\Struct\V1\Token;
use Swag\PayPal\RestApi\ApiContextFactoryInterface;
use Swag\PayPal\RestApi\Exception\PayPalApiException;

#[Package('checkout')]
class TokenResource implements TokenResourceInterface
{
    /**
     * @internal
     */
    public function __construct(
        private readonly TokenGatewayInterface $tokenGateway,
        private readonly ApiContextFactoryInterface $apiContextFactory,
    ) {
    }

    /**
     * @throws PayPalApiException
     */
    public function getToken(?string $salesChannelId): Token
    {
        try {
            return $this->tokenGateway->getToken($this->apiContextFactory->getApiContext($salesChannelId));
        } catch (ApiException $e) {
            throw PayPalApiException::from($e);
        }
    }

    /**
     * @throws PayPalApiException
     * @throws \InvalidArgumentException
     */
    public function getUserIdToken(?string $salesChannelId, ?string $targetCustomerId = null): Token
    {
        $context = $this->apiContextFactory->getApiContext($salesChannelId);

        if (!($oauthContext = $context->getOAuthContext()) instanceof CredentialsOAuthContext) {
            throw new \InvalidArgumentException($this->apiContextFactory::class . ' should have returned a context including ' . CredentialsOAuthContext::class);
        }

        return $this->tokenGateway->getToken($context->withOAuthContext($oauthContext->intoUserIdContext($targetCustomerId)));
    }
}
