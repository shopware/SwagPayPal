<?php declare(strict_types=1);
/*
 * (c) shopware AG <info@shopware.com>
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Swag\PayPal\Test\Pos\Mock\Client;

use Psr\Log\LoggerInterface;
use Psr\Log\NullLogger;
use Swag\PayPal\Pos\Api\Authentification\OAuthCredentials;
use Swag\PayPal\Pos\Client\PosClient;
use Swag\PayPal\Pos\Client\PosClientFactory;
use Swag\PayPal\Pos\Resource\TokenResource;
use Swag\PayPal\Test\Mock\CacheMock;

class PosClientFactoryMock extends PosClientFactory
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

    public function createPosClient(string $baseUri, string $apiKey): PosClient
    {
        $credentials = new OAuthCredentials();
        $credentials->setApiKey($apiKey);

        return new PosClientMock($baseUri, $this->tokenResource, $credentials, $this->logger);
    }
}
