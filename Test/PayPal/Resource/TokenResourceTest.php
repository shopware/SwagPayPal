<?php declare(strict_types=1);
/**
 * (c) shopware AG <info@shopware.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace SwagPayPal\Test\PayPal\Resource;

use DateTime;
use PHPUnit\Framework\TestCase;
use Shopware\Core\Framework\Context;
use SwagPayPal\PayPal\Api\OAuthCredentials;
use SwagPayPal\PayPal\Api\Token;
use SwagPayPal\PayPal\Resource\TokenResource;
use SwagPayPal\Test\Mock\CacheMock;
use SwagPayPal\Test\Mock\PayPal\Client\TokenClientFactoryMock;
use SwagPayPal\Test\Mock\PayPal\Client\TokenClientMock;

class TokenResourceTest extends TestCase
{
    public function testGetToken(): void
    {
        $tokenResource = $this->getTokenResource();

        $context = Context::createDefaultContext();
        $token = $tokenResource->getToken(new OAuthCredentials(), $context, 'url');

        $dateNow = new DateTime('now');

        self::assertInstanceOf(Token::class, $token);
        self::assertSame(TokenClientMock::ACCESS_TOKEN, $token->getAccessToken());
        self::assertSame(TokenClientMock::TOKEN_TYPE, $token->getTokenType());
        self::assertInstanceOf(DateTime::class, $token->getExpireDateTime());
        self::assertTrue($dateNow < $token->getExpireDateTime());
    }

    private function getTokenResource(): TokenResource
    {
        return new TokenResource(new CacheMock(), new TokenClientFactoryMock());
    }
}
