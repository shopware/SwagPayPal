<?php declare(strict_types=1);
/*
 * (c) shopware AG <info@shopware.com>
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Swag\PayPal\Pos\Client;

use Psr\Log\LoggerInterface;
use Shopware\Core\Framework\Log\Package;
use Swag\PayPal\Pos\Api\Authentication\OAuthCredentials;
use Swag\PayPal\Pos\Resource\TokenResource;

#[Package('checkout')]
class PosClientFactory
{
    private TokenResource $tokenResource;

    private LoggerInterface $logger;

    /**
     * @internal
     */
    public function __construct(
        TokenResource $tokenResource,
        LoggerInterface $logger,
    ) {
        $this->tokenResource = $tokenResource;
        $this->logger = $logger;
    }

    public function getPosClient(string $baseUri, string $apiKey): PosClient
    {
        $credentials = new OAuthCredentials();
        $credentials->setApiKey($apiKey);

        return new PosClient($baseUri, $this->tokenResource, $credentials, $this->logger);
    }
}
