<?php declare(strict_types=1);
/*
 * (c) shopware AG <info@shopware.com>
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Swag\PayPal\Test\Mock\PayPal\Client;

use Psr\Log\LoggerInterface;
use Swag\PayPal\PayPal\Api\OAuthCredentials;
use Swag\PayPal\PayPal\Client\TokenClient;

class TokenClientMock extends TokenClient
{
    public function __construct(OAuthCredentials $credentials, string $url, LoggerInterface $logger)
    {
        parent::__construct($credentials, $url, $logger);
        $this->client = new GuzzleClientMock([
            'base_uri' => $url,
            'headers' => [
                'Authorization' => (string) $credentials,
            ],
        ]);
    }
}
