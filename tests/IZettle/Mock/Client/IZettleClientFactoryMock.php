<?php declare(strict_types=1);
/*
 * (c) shopware AG <info@shopware.com>
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Swag\PayPal\Test\IZettle\Mock\Client;

use Psr\Log\LoggerInterface;
use Psr\Log\NullLogger;
use Swag\PayPal\IZettle\Api\Authentification\OAuthCredentials;
use Swag\PayPal\IZettle\Client\IZettleClient;
use Swag\PayPal\IZettle\Client\IZettleClientFactory;
use Swag\PayPal\IZettle\Resource\TokenResource;
use Swag\PayPal\Test\Mock\CacheMock;

class IZettleClientFactoryMock extends IZettleClientFactory
{
    /**
     * @var LoggerInterface
     */
    private $logger;

    /**
     * @var TokenResource
     */
    private $tokenResource;

    public function __construct()
    {
        $this->logger = new NullLogger();
        $this->tokenResource = new TokenResource(
            new CacheMock(),
            new TokenClientFactoryMock()
        );
        parent::__construct($this->tokenResource, new NullLogger());
    }

    public function createIZettleClient(string $baseUri, string $apiKey): IZettleClient
    {
        $credentials = new OAuthCredentials();
        $credentials->setApiKey($apiKey);

        return new IZettleClientMock($baseUri, $this->tokenResource, $credentials, $this->logger);
    }
}
