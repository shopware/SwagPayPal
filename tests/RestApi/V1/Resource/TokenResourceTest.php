<?php declare(strict_types=1);
/*
 * (c) shopware AG <info@shopware.com>
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Swag\PayPal\Test\RestApi\V1\Resource;

use PHPUnit\Framework\TestCase;
use Shopware\Core\Framework\Log\Package;
use Swag\PayPal\RestApi\V1\Resource\TokenResource;
use Swag\PayPal\Test\Mock\PayPal\Client\_fixtures\V1\CreateTokenResponseFixture;
use Swag\PayPal\Test\Mock\PayPalSDK\ApiContextFactoryMock;
use Swag\PayPal\Test\Mock\PayPalSDK\GatewayTestBehaviour;

/**
 * @internal
 */
#[Package('checkout')]
class TokenResourceTest extends TestCase
{
    use GatewayTestBehaviour;

    public function testGetToken(): void
    {
        $token = $this->getTokenResource()->getToken('salesChannelId');

        $dateNow = new \DateTime('now');

        static::assertSame(CreateTokenResponseFixture::ACCESS_TOKEN, $token->getAccessToken());
        static::assertSame(CreateTokenResponseFixture::TOKEN_TYPE, $token->getTokenType());
        static::assertTrue($dateNow < $token->getExpireDateTime());
    }

    private function getTokenResource(): TokenResource
    {
        return new TokenResource(self::tokenGateway(), new ApiContextFactoryMock());
    }
}
