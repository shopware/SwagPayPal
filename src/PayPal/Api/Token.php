<?php declare(strict_types=1);
/**
 * (c) shopware AG <info@shopware.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Swag\PayPal\PayPal\Api;

use Swag\PayPal\PayPal\Api\Common\PayPalStruct;

class Token extends PayPalStruct
{
    /**
     * Scopes expressed in the form of resource URL endpoints. The value of the scope parameter
     * is expressed as a list of space-delimited, case-sensitive strings.
     *
     * @var string
     */
    private $scope;

    /**
     * @var string
     */
    private $nonce;

    /**
     * The access token issued by PayPal. After the access token
     * expires (see $expiresIn), you must request a new access token.
     *
     * @var string
     */
    private $accessToken;

    /**
     * The type of the token issued as described in OAuth2.0 RFC6749,
     * Section 7.1. Value is case insensitive.
     *
     * @var string
     */
    private $tokenType;

    /**
     * @var string
     */
    private $appId;

    /**
     * The lifetime of the access token, in seconds.
     *
     * @var int
     */
    private $expiresIn;

    /**
     * Calculated expiration date
     *
     * @var \DateTime
     */
    private $expireDateTime;

    public function assign(array $arrayDataWithSnakeCaseKeys): PayPalStruct
    {
        /** @var Token $newToken */
        $newToken = parent::assign($arrayDataWithSnakeCaseKeys);

        //Calculate the expiration date manually
        $expirationDateTime = new \DateTime();
        $interval = \DateInterval::createFromDateString($newToken->getExpiresIn() . ' seconds');
        $expirationDateTime = $expirationDateTime->add($interval);

        $newToken->setExpireDateTime($expirationDateTime);

        return $newToken;
    }

    public function getAccessToken(): string
    {
        return $this->accessToken;
    }

    public function getTokenType(): string
    {
        return $this->tokenType;
    }

    public function getExpireDateTime(): \DateTime
    {
        return $this->expireDateTime;
    }

    protected function setScope(string $scope): void
    {
        $this->scope = $scope;
    }

    protected function setAccessToken(string $accessToken): void
    {
        $this->accessToken = $accessToken;
    }

    protected function setTokenType(string $tokenType): void
    {
        $this->tokenType = $tokenType;
    }

    protected function setExpiresIn(int $expiresIn): void
    {
        $this->expiresIn = $expiresIn;
    }

    protected function setNonce(string $nonce): void
    {
        $this->nonce = $nonce;
    }

    protected function setAppId(string $appId): void
    {
        $this->appId = $appId;
    }

    private function getExpiresIn(): int
    {
        return $this->expiresIn;
    }

    private function setExpireDateTime(\DateTime $expireDateTime): void
    {
        $this->expireDateTime = $expireDateTime;
    }
}
