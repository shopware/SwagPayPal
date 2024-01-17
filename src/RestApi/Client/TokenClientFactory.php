<?php

declare(strict_types=1);
/*
 * (c) shopware AG <info@shopware.com>
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Swag\PayPal\RestApi\Client;

use Psr\Log\LoggerInterface;
use Shopware\Core\Framework\Log\Package;
use Swag\PayPal\RestApi\V1\Api\OAuthCredentials;

#[Package('checkout')]
class TokenClientFactory
{
    private LoggerInterface $logger;

    /**
     * @internal
     */
    public function __construct(LoggerInterface $logger)
    {
        $this->logger = $logger;
    }

    public function createTokenClient(OAuthCredentials $credentials): TokenClient
    {
        return new TokenClient($credentials, $this->logger);
    }
}
