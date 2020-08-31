<?php declare(strict_types=1);
/*
 * (c) shopware AG <info@shopware.com>
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Swag\PayPal\Pos\Client;

use Psr\Log\LoggerInterface;
use Swag\PayPal\Pos\Api\Authentification\OAuthCredentials;
use Swag\PayPal\Pos\Resource\TokenResource;

class PosClientFactory
{
    /**
     * @var TokenResource
     */
    private $tokenResource;

    /**
     * @var LoggerInterface
     */
    private $logger;

    public function __construct(TokenResource $tokenResource, LoggerInterface $logger)
    {
        $this->tokenResource = $tokenResource;
        $this->logger = $logger;
    }

    public function createPosClient(string $baseUri, string $apiKey): PosClient
    {
        $credentials = new OAuthCredentials();
        $credentials->setApiKey($apiKey);

        return new PosClient($baseUri, $this->tokenResource, $credentials, $this->logger);
    }
}
