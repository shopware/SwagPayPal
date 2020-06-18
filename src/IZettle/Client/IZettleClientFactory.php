<?php declare(strict_types=1);
/*
 * (c) shopware AG <info@shopware.com>
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Swag\PayPal\IZettle\Client;

use Psr\Log\LoggerInterface;
use Swag\PayPal\IZettle\Api\Authentification\OAuthCredentials;
use Swag\PayPal\IZettle\Resource\TokenResource;

class IZettleClientFactory
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

    public function createIZettleClient(string $baseUri, string $apiKey): IZettleClient
    {
        $credentials = new OAuthCredentials();
        $credentials->setApiKey($apiKey);

        return new IZettleClient($baseUri, $this->tokenResource, $credentials, $this->logger);
    }
}
