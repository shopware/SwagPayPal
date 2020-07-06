<?php declare(strict_types=1);
/*
 * (c) shopware AG <info@shopware.com>
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Swag\PayPal\Test\IZettle\Mock\Client;

use Psr\Log\LoggerInterface;
use Swag\PayPal\IZettle\Api\Authentification\OAuthCredentials;
use Swag\PayPal\IZettle\Client\IZettleClient;
use Swag\PayPal\IZettle\Resource\TokenResource;

class IZettleClientMock extends IZettleClient
{
    public function __construct(string $baseUri, TokenResource $tokenResource, OAuthCredentials $credentials, LoggerInterface $logger)
    {
        parent::__construct($baseUri, $tokenResource, $credentials, $logger);
        $this->client = new GuzzleClientMock([
            'base_uri' => $baseUri,
        ]);
    }
}
