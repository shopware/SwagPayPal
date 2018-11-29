<?php declare(strict_types=1);
/**
 * (c) shopware AG <info@shopware.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace SwagPayPal\Service;

use GuzzleHttp\Exception\ClientException;
use SwagPayPal\PayPal\Api\OAuthCredentials;
use SwagPayPal\PayPal\BaseURL;
use SwagPayPal\PayPal\Client\Exception\PayPalInvalidApiCredentialsException;
use SwagPayPal\PayPal\Resource\TokenResource;
use Symfony\Component\HttpFoundation\Response;

class ApiCredentialTestService
{
    /**
     * @var TokenResource
     */
    private $tokenResource;

    public function __construct(TokenResource $tokenResource)
    {
        $this->tokenResource = $tokenResource;
    }

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
        }
    }
}
