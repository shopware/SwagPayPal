<?php declare(strict_types=1);
/*
 * (c) shopware AG <info@shopware.com>
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Swag\PayPal\IZettle\Setting\Service;

use GuzzleHttp\Exception\ClientException;
use Swag\PayPal\IZettle\Api\Authentification\OAuthCredentials;
use Swag\PayPal\IZettle\Resource\TokenResource;
use Swag\PayPal\IZettle\Setting\Exception\IZettleInvalidApiCredentialsException;
use Symfony\Component\HttpFoundation\Response;

class ApiCredentialService
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
     * @throws IZettleInvalidApiCredentialsException
     */
    public function testApiCredentials(string $apiKey): bool
    {
        $credentials = new OAuthCredentials();
        $credentials->setApiKey($apiKey);

        try {
            return $this->tokenResource->testApiCredentials($credentials);
        } catch (ClientException $ce) {
            if ($ce->getCode() === Response::HTTP_UNAUTHORIZED) {
                throw new IZettleInvalidApiCredentialsException();
            }

            throw $ce;
        }
    }
}
