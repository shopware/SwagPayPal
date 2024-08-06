<?php declare(strict_types=1);
/*
 * (c) shopware AG <info@shopware.com>
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Swag\PayPal\Test\PayPal\ApiV1\Resource;

use PHPUnit\Framework\TestCase;
use Psr\Log\NullLogger;
use Shopware\Core\Framework\Log\Package;
use Swag\PayPal\RestApi\BaseURL;
use Swag\PayPal\RestApi\V1\Api\OAuthCredentials;
use Swag\PayPal\RestApi\V1\Resource\CredentialsResource;
use Swag\PayPal\RestApi\V1\Service\TokenValidator;
use Swag\PayPal\Test\Mock\PayPal\Client\CredentialsClientFactoryMock;
use Swag\PayPal\Test\Mock\PayPal\Client\TokenClientFactoryMock;

/**
 * @internal
 *
 * @deprecated tag:v10.0.0 - the only method covered here will be removed
 */
#[Package('checkout')]
class CredentialsResourceTest extends TestCase
{
    public function testTestApiCredentials(): void
    {
        $logger = new NullLogger();
        $credentialsResource = new CredentialsResource(
            new TokenClientFactoryMock($logger),
            new CredentialsClientFactoryMock($logger),
            new TokenValidator()
        );
        $credentials = new OAuthCredentials();
        $credentials->setRestId('restId');
        $credentials->setRestSecret('restSecret');
        $credentials->setUrl(BaseURL::SANDBOX);
        $result = $credentialsResource->testApiCredentials($credentials);

        static::assertTrue($result);
    }
}
