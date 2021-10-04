<?php declare(strict_types=1);
/*
 * (c) shopware AG <info@shopware.com>
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Swag\PayPal\Test\PayPal\ApiV1\Resource;

use PHPUnit\Framework\TestCase;
use Swag\PayPal\RestApi\BaseURL;
use Swag\PayPal\RestApi\V1\Api\OAuthCredentials;
use Swag\PayPal\RestApi\V1\Resource\CredentialsResource;
use Swag\PayPal\RestApi\V1\Service\TokenValidator;
use Swag\PayPal\Test\Mock\LoggerMock;
use Swag\PayPal\Test\Mock\PayPal\Client\CredentialsClientFactoryMock;
use Swag\PayPal\Test\Mock\PayPal\Client\TokenClientFactoryMock;

class CredentialsResourceTest extends TestCase
{
    public function testTestApiCredentials(): void
    {
        $logger = new LoggerMock();
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
