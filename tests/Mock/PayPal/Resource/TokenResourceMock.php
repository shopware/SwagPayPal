<?php declare(strict_types=1);
/**
 * (c) shopware AG <info@shopware.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace SwagPayPal\Test\Mock\PayPal\Resource;

use GuzzleHttp\Exception\ClientException;
use GuzzleHttp\Psr7\Request;
use GuzzleHttp\Psr7\Response;
use SwagPayPal\PayPal\Api\OAuthCredentials;
use SwagPayPal\PayPal\Api\Token;
use SwagPayPal\PayPal\Resource\TokenResource;
use SwagPayPal\Test\Helper\ConstantsForTesting;
use SwagPayPal\Test\Setting\Service\ApiCredentialTestServiceTest;
use Symfony\Component\HttpFoundation\Response as SymfonyResponse;

class TokenResourceMock extends TokenResource
{
    public const GENERAL_CLIENT_EXCEPTION_MESSAGE = 'generalClientExceptionMessage';

    public function getToken(OAuthCredentials $credentials, string $url, string $cacheId): Token
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
        if ($this->getAuthenticationHeader(ConstantsForTesting::VALID_CLIENT_ID) === (string) $credentials) {
            return true;
        }

        if ($this->getAuthenticationHeader(ApiCredentialTestServiceTest::INVALID_API_CLIENT_ID) === (string) $credentials) {
            throw $this->createClientException(SymfonyResponse::HTTP_UNAUTHORIZED);
        }

        throw $this->createClientException(SymfonyResponse::HTTP_NOT_FOUND);
    }

    private function getAuthenticationHeader(string $restId): string
    {
        $validOauth = new OAuthCredentials();
        $validOauth->setRestId($restId);
        $validOauth->setRestSecret(ConstantsForTesting::VALID_CLIENT_SECRET);

        return (string) $validOauth;
    }

    private function createClientException(int $httpCode): ClientException
    {
        return new ClientException(
            self::GENERAL_CLIENT_EXCEPTION_MESSAGE,
            new Request('', ''),
            new Response($httpCode)
        );
    }
}
