<?php declare(strict_types=1);
/**
 * (c) shopware AG <info@shopware.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace SwagPayPal\PayPal\Struct;

use DateInterval;
use DateTime;

class Token
{
    /**
     * Scopes expressed in the form of resource URL endpoints. The value of the scope parameter
     * is expressed as a list of space-delimited, case-sensitive strings.
     *
     * @var string
     */
    private $scope;

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
     * The lifetime of the access token, in seconds.
     *
     * @var int
     */
    private $expiresIn;

    /**
     * Calculated expiration date
     *
     * @var DateTime
     */
    private $expireDateTime;

    public function getScope(): string
    {
        return $this->scope;
    }

    public function getAccessToken(): string
    {
        return $this->accessToken;
    }

    public function getTokenType(): string
    {
        return $this->tokenType;
    }

    public function getExpiresIn(): int
    {
        return $this->expiresIn;
    }

    public function getExpireDateTime(): DateTime
    {
        return $this->expireDateTime;
    }

    public static function fromArray(array $data): Token
    {
        $token = new self();

        $token->setAccessToken($data['access_token']);
        $token->setExpiresIn((int) $data['expires_in']);
        $token->setScope($data['scope']);
        $token->setTokenType($data['token_type']);

        //Calculate the expiration date manually
        $expirationDateTime = new DateTime();
        $interval = DateInterval::createFromDateString($token->getExpiresIn() . ' seconds');
        $expirationDateTime = $expirationDateTime->add($interval);

        $token->setExpireDateTime($expirationDateTime);

        return $token;
    }

    private function setScope(string $scope): void
    {
        $this->scope = $scope;
    }

    private function setAccessToken(string $accessToken): void
    {
        $this->accessToken = $accessToken;
    }

    private function setTokenType(string $tokenType): void
    {
        $this->tokenType = $tokenType;
    }

    private function setExpiresIn(int $expiresIn): void
    {
        $this->expiresIn = $expiresIn;
    }

    private function setExpireDateTime(DateTime $expireDateTime): void
    {
        $this->expireDateTime = $expireDateTime;
    }
}
