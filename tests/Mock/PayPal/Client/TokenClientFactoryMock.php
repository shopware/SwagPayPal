<?php declare(strict_types=1);
/*
 * (c) shopware AG <info@shopware.com>
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Swag\PayPal\Test\Mock\PayPal\Client;

use Psr\Log\LoggerInterface;
use Swag\PayPal\RestApi\Client\TokenClient;
use Swag\PayPal\RestApi\Client\TokenClientFactory;
use Swag\PayPal\RestApi\V1\Api\OAuthCredentials;

class TokenClientFactoryMock extends TokenClientFactory
{
    /**
     * @var LoggerInterface
     */
    private $logger;

    public function __construct(LoggerInterface $logger)
    {
        $this->logger = $logger;
        parent::__construct($logger);
    }

    public function createTokenClient(OAuthCredentials $credentials, string $url): TokenClient
    {
        return new TokenClientMock($credentials, $url, $this->logger);
    }
}
