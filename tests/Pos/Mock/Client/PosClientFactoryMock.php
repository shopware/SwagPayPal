<?php

declare(strict_types=1);
/*
 * (c) shopware AG <info@shopware.com>
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Swag\PayPal\Test\Pos\Mock\Client;

use Psr\Log\LoggerInterface;
use Psr\Log\NullLogger;
use Shopware\Core\Framework\Log\Package;
use Swag\PayPal\Pos\Api\Authentication\OAuthCredentials;
use Swag\PayPal\Pos\Client\PosClient;
use Swag\PayPal\Pos\Client\PosClientFactory;
use Swag\PayPal\Pos\Resource\TokenResource;
use Swag\PayPal\Test\Mock\CacheMock;

/**
 * @internal
 */
#[Package('checkout')]
class PosClientFactoryMock extends PosClientFactory
{
    private LoggerInterface $logger;

    private TokenResource $tokenResource;

    private ?PosClientMock $posClient = null;

    public function __construct()
    {
        $this->logger = new NullLogger();
        $this->tokenResource = new TokenResource(
            new CacheMock(),
            new TokenClientFactoryMock()
        );
        parent::__construct($this->tokenResource, new NullLogger());
    }

    public function getPosClient(string $baseUri, string $apiKey): PosClient
    {
        $credentials = new OAuthCredentials();
        $credentials->setApiKey($apiKey);

        if ($this->posClient === null) {
            $this->posClient = new PosClientMock($baseUri, $this->tokenResource, $credentials, $this->logger);
        }

        return $this->posClient;
    }
}
