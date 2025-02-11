<?php declare(strict_types=1);
/*
 * (c) shopware AG <info@shopware.com>
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Swag\PayPal\Test\RestApi\V1\Resource;

use PHPUnit\Framework\TestCase;
use Psr\Log\NullLogger;
use Shopware\Core\Framework\Log\Package;
use Shopware\Core\Test\TestDefaults;
use Swag\PayPal\RestApi\V1\Resource\AuthorizationResource;
use Swag\PayPal\Test\Mock\PayPal\Client\_fixtures\V1\GetResourceAuthorizeResponseFixture;
use Swag\PayPal\Test\Mock\PayPal\Client\PayPalClientFactoryMock;

/**
 * @internal
 */
#[Package('checkout')]
class AuthorizationResourceTest extends TestCase
{
    public function testGet(): void
    {
        $authorizationResponse = $this->createAuthorizationResource()->get(
            'authorizationId',
            TestDefaults::SALES_CHANNEL
        );

        $authorization = \json_encode($authorizationResponse);
        static::assertNotFalse($authorization);

        $authorizationArray = \json_decode($authorization, true);

        static::assertSame(GetResourceAuthorizeResponseFixture::ID, $authorizationArray['id']);
    }

    private function createAuthorizationResource(): AuthorizationResource
    {
        return new AuthorizationResource(new PayPalClientFactoryMock(new NullLogger()));
    }
}
