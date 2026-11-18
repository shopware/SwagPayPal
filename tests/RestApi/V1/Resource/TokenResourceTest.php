<?php declare(strict_types=1);
/*
 * (c) shopware AG <info@shopware.com>
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Swag\PayPal\Test\RestApi\V1\Resource;

use PHPUnit\Framework\TestCase;
use Shopware\Core\Framework\Log\Package;
use Shopware\Core\Test\Generator;
use Shopware\PayPalSDK\Contract\Context\ApiContextInterface;
use Shopware\PayPalSDK\Contract\Gateway\TokenGatewayInterface;
use Shopware\PayPalSDK\Struct\V1\Token;
use Swag\PayPal\RestApi\Context\VaultClaimOAuthContext;
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

    public function testGetVaultClientToken(): void
    {
        $token = (new Token())->assign(CreateTokenResponseFixture::getClientToken());

        $tokenGateway = $this->createMock(TokenGatewayInterface::class);
        $tokenGateway
            ->expects($this->once())
            ->method('getToken')
            ->with(static::callback(static function (ApiContextInterface $context): bool {
                $oauthContext = $context->getOAuthContext();

                return $oauthContext instanceof VaultClaimOAuthContext
                    && $oauthContext->getBody()['response_type'] === 'client_token'
                    && $oauthContext->getBody()['claims[]'] === 'vault_id:vault-id';
            }))
            ->willReturn($token);

        $resource = new TokenResource($tokenGateway, new ApiContextFactoryMock());

        static::assertSame($token, $resource->getVaultClientToken(Generator::generateSalesChannelContext(), 'vault-id'));
    }

    private function getTokenResource(): TokenResource
    {
        return new TokenResource(self::tokenGateway(), new ApiContextFactoryMock());
    }
}
