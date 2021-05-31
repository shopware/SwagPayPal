<?php declare(strict_types=1);
/*
 * (c) shopware AG <info@shopware.com>
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Swag\PayPal\RestApi\Client;

use Psr\Log\LoggerInterface;
use Swag\PayPal\RestApi\V1\Api\OAuthCredentials;

class TokenClientFactory
{
    private LoggerInterface $logger;

    public function __construct(LoggerInterface $logger)
    {
        $this->logger = $logger;
    }

    /**
     * @deprecated tag:v4.0.0 - parameter $url will be removed, is placed in OAuthCredentials now
     */
    public function createTokenClient(OAuthCredentials $credentials, string $url): TokenClient
    {
        return new TokenClient($credentials, $url, $this->logger);
    }
}
