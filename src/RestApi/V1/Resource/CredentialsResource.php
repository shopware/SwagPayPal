<?php declare(strict_types=1);
/*
 * (c) shopware AG <info@shopware.com>
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Swag\PayPal\RestApi\V1\Resource;

use Shopware\Core\Framework\Log\Package;
use Swag\PayPal\RestApi\Client\CredentialsClientFactory;

#[Package('checkout')]
class CredentialsResource
{
    /**
     * @internal
     */
    public function __construct(
        private readonly CredentialsClientFactory $credentialsClientFactory,
    ) {
    }

    public function getClientCredentials(
        string $authCode,
        string $sharedId,
        string $nonce,
        string $url,
        string $partnerId,
    ): array {
        $credentialsClient = $this->credentialsClientFactory->createCredentialsClient($url);
        $accessToken = $credentialsClient->getAccessToken($authCode, $sharedId, $nonce);

        return $credentialsClient->getCredentials($accessToken, $partnerId);
    }
}
