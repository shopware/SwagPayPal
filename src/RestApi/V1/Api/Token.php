<?php declare(strict_types=1);
/*
 * (c) shopware AG <info@shopware.com>
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Swag\PayPal\RestApi\V1\Api;

use OpenApi\Annotations as OA;
use Swag\PayPal\RestApi\PayPalApiStruct;

/**
 * @OA\Schema(schema="swag_paypal_v1_token")
 */
final class Token extends PayPalApiStruct
{
    /**
     * Scopes expressed in the form of resource URL endpoints. The value of the scope parameter
     * is expressed as a list of space-delimited, case-sensitive strings.
     *
     * @OA\Property(type="string")
     */
    private string $scope;

    /**
     * @OA\Property(type="string")
     */
    private string $nonce;

    /**
     * The access token issued by PayPal. After the access token
     * expires (see $expiresIn), you must request a new access token.
     *
     * @OA\Property(type="string")
     */
    private string $accessToken;

    /**
     * The type of the token issued as described in OAuth2.0 RFC6749,
     * Section 7.1. Value is case insensitive.
     *
     * @OA\Property(type="string")
     */
    private string $tokenType;

    /**
     * @OA\Property(type="string")
     */
    private string $appId;

    /**
     * The lifetime of the access token, in seconds.
     *
     * @OA\Property(type="integer")
     */
    private int $expiresIn;

    /**
     * Calculated expiration date
     *
     * @OA\Property(type="date")
     */
    private \DateTime $expireDateTime;

    /**
     * @return static
     */
    public function assign(array $arrayDataWithSnakeCaseKeys)
    {
        $newToken = parent::assign($arrayDataWithSnakeCaseKeys);

        //Calculate the expiration date manually
        $expirationDateTime = new \DateTime();
        $interval = \DateInterval::createFromDateString(\sprintf('%s seconds', $newToken->getExpiresIn()));
        $expirationDateTime = $expirationDateTime->add($interval ?: new \DateInterval('PT0S'));

        $newToken->setExpireDateTime($expirationDateTime);

        return $newToken;
    }

    public function getScope(): string
    {
        return $this->scope;
    }

    public function setScope(string $scope): void
    {
        $this->scope = $scope;
    }

    public function getNonce(): string
    {
        return $this->nonce;
    }

    public function setNonce(string $nonce): void
    {
        $this->nonce = $nonce;
    }

    public function getAccessToken(): string
    {
        return $this->accessToken;
    }

    public function setAccessToken(string $accessToken): void
    {
        $this->accessToken = $accessToken;
    }

    public function getTokenType(): string
    {
        return $this->tokenType;
    }

    public function setTokenType(string $tokenType): void
    {
        $this->tokenType = $tokenType;
    }

    public function getAppId(): string
    {
        return $this->appId;
    }

    public function setAppId(string $appId): void
    {
        $this->appId = $appId;
    }

    public function getExpiresIn(): int
    {
        return $this->expiresIn;
    }

    public function setExpiresIn(int $expiresIn): void
    {
        $this->expiresIn = $expiresIn;
    }

    public function getExpireDateTime(): \DateTime
    {
        return $this->expireDateTime;
    }

    public function setExpireDateTime(\DateTime $expireDateTime): void
    {
        $this->expireDateTime = $expireDateTime;
    }
}
