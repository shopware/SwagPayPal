<?php declare(strict_types=1);
/*
 * (c) shopware AG <info@shopware.com>
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Swag\PayPal\Setting\Service;

use GuzzleHttp\Exception\ClientException;
use Swag\PayPal\PayPal\Api\OAuthCredentials;
use Swag\PayPal\PayPal\BaseURL;
use Swag\PayPal\PayPal\PartnerId;
use Swag\PayPal\PayPal\Resource\TokenResource;
use Swag\PayPal\Setting\Exception\PayPalInvalidApiCredentialsException;
use Symfony\Component\HttpFoundation\Response;

class ApiCredentialService implements ApiCredentialServiceInterface
{
    /**
     * @var TokenResource
     */
    private $tokenResource;

    public function __construct(TokenResource $tokenResource)
    {
        $this->tokenResource = $tokenResource;
    }

    /**
     * @throws PayPalInvalidApiCredentialsException
     */
    public function testApiCredentials(string $clientId, string $clientSecret, bool $sandboxActive): bool
    {
        $credentials = new OAuthCredentials();
        $credentials->setRestId($clientId);
        $credentials->setRestSecret($clientSecret);
        $url = $sandboxActive ? BaseURL::SANDBOX : BaseURL::LIVE;

        try {
            return $this->tokenResource->testApiCredentials($credentials, $url);
        } catch (ClientException $ce) {
            if ($ce->getCode() === Response::HTTP_UNAUTHORIZED) {
                throw new PayPalInvalidApiCredentialsException();
            }

            throw $ce;
        }
    }

    public function getApiCredentials(string $authCode, string $sharedId, string $nonce, bool $sandboxActive): array
    {
        $url = $sandboxActive ? BaseURL::SANDBOX : BaseURL::LIVE;
        $partnerId = $sandboxActive ? PartnerId::SANDBOX : PartnerId::LIVE;

        return $this->tokenResource->getClientCredentials($authCode, $sharedId, $nonce, $url, $partnerId);
    }
}
