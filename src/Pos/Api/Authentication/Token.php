<?php declare(strict_types=1);
/*
 * (c) shopware AG <info@shopware.com>
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Swag\PayPal\Pos\Api\Authentication;

use Swag\PayPal\Pos\Api\Common\PosStruct;

final class Token extends PosStruct
{
    /**
     * The access token issued by Zettle. After the access token
     * expires (see $expiresIn), you must request a new access token.
     *
     * @var string
     */
    protected $accessToken;

    /**
     * @var string
     */
    protected $refreshToken;

    /**
     * The lifetime of the access token, in seconds.
     *
     * @var int
     */
    protected $expiresIn;

    /**
     * Calculated expiration date
     *
     * @var \DateTime
     */
    protected $expireDateTime;

    public function assign(array $arrayDataWithSnakeCaseKeys): Token
    {
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

    public function setAccessToken(string $accessToken): void
    {
        $this->accessToken = $accessToken;
    }

    public function getRefreshToken(): string
    {
        return $this->refreshToken;
    }

    public function setRefreshToken(string $refreshToken): void
    {
        $this->refreshToken = $refreshToken;
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
