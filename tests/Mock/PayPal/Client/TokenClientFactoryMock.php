<?php

declare(strict_types=1);
/*
 * (c) shopware AG <info@shopware.com>
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Swag\PayPal\Test\Mock\PayPal\Client;

use Psr\Log\LoggerInterface;
use Shopware\Core\Framework\Log\Package;
use Swag\PayPal\RestApi\Client\TokenClient;
use Swag\PayPal\RestApi\Client\TokenClientFactory;
use Swag\PayPal\RestApi\V1\Api\OAuthCredentials;

/**
 * @internal
 */
#[Package('checkout')]
class TokenClientFactoryMock extends TokenClientFactory
{
    private LoggerInterface $logger;

    public function __construct(LoggerInterface $logger)
    {
        $this->logger = $logger;
        parent::__construct($logger);
    }

    public function createTokenClient(OAuthCredentials $credentials): TokenClient
    {
        return new TokenClientMock($credentials, $this->logger);
    }
}
