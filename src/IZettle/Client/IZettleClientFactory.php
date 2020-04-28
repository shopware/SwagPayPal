<?php declare(strict_types=1);
/*
 * (c) shopware AG <info@shopware.com>
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Swag\PayPal\IZettle\Client;

use Psr\Log\LoggerInterface;
use Swag\PayPal\IZettle\Api\OAuthCredentials;
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

    public function createIZettleClient(string $baseUri, string $username, string $password): IZettleClient
    {
        $credentials = new OAuthCredentials();
        $credentials->setUsername($username);
        $credentials->setPassword($password);

        return new IZettleClient($baseUri, $this->tokenResource, $credentials, $this->logger);
    }
}
