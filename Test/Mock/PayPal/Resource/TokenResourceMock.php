<?php declare(strict_types=1);
/**
 * (c) shopware AG <info@shopware.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace SwagPayPal\Test\Mock\PayPal\Resource;

use Shopware\Core\Framework\Context;
use SwagPayPal\PayPal\Api\OAuthCredentials;
use SwagPayPal\PayPal\Api\Token;
use SwagPayPal\PayPal\Resource\TokenResource;
use SwagPayPal\Test\Helper\ConstantsForTesting;

class TokenResourceMock extends TokenResource
{
    public function getToken(OAuthCredentials $credentials, Context $context, string $url): Token
    {
        $token = new Token();
        $token->assign([
            'token_type' => 'testTokenType',
            'access_token' => 'testAccessToken',
            'expires_in' => 100,
        ]);

        return $token;
    }

    public function testApiCredentials(OAuthCredentials $credentials, string $url): bool
    {
        return 'Basic ' . base64_encode(ConstantsForTesting::VALID_CLIENT_ID . ':' . ConstantsForTesting::VALID_CLIENT_SECRET) === (string) $credentials;
    }
}
