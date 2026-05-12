<?php declare(strict_types=1);
/*
 * (c) shopware AG <info@shopware.com>
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Swag\PayPal\Test\Pos\Mock\Client;

use Psr\Log\LoggerInterface;
use Shopware\Core\Framework\Log\Package;
use Swag\PayPal\Pos\Api\Authentication\OAuthCredentials;
use Swag\PayPal\Pos\Client\PosClient;
use Swag\PayPal\Pos\Resource\TokenResource;

/**
 * @internal
 */
#[Package('checkout')]
class PosClientMock extends PosClient
{
    public function __construct(
        string $baseUri,
        TokenResource $tokenResource,
        OAuthCredentials $credentials,
        LoggerInterface $logger,
    ) {
        parent::__construct($baseUri, $tokenResource, $credentials, $logger);
        $this->client = new GuzzleClientMock([
            'base_uri' => $baseUri,
        ]);
    }
}
