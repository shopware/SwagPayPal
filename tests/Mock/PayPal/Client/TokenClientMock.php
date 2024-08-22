<?php declare(strict_types=1);
/*
 * (c) shopware AG <info@shopware.com>
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Swag\PayPal\Test\Mock\PayPal\Client;

use Psr\Log\LoggerInterface;
use Shopware\Core\Framework\Log\Package;
use Swag\PayPal\RestApi\Client\TokenClient;
use Swag\PayPal\RestApi\V1\Api\OAuthCredentials;

/**
 * @internal
 */
#[Package('checkout')]
class TokenClientMock extends TokenClient
{
    public function __construct(
        OAuthCredentials $credentials,
        LoggerInterface $logger
    ) {
        parent::__construct($credentials, $logger);
        $this->client = new GuzzleClientMock([
            'base_uri' => $credentials->getUrl(),
            'headers' => [
                'Authorization' => (string) $credentials,
            ],
        ]);
    }
}
