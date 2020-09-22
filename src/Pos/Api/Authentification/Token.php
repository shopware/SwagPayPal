<?php declare(strict_types=1);
/*
 * (c) shopware AG <info@shopware.com>
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Swag\PayPal\Pos\Api\Authentification;

use Swag\PayPal\Pos\Api\Common\PosStruct;

final class Token extends PosStruct
{
    /**
     * The access token issued by iZettle. After the access token
     * expires (see $expiresIn), you must request a new access token.
     *
     * @var string
     */
    private $accessToken;

    /**
     * @var string
     */
    private $refreshToken;

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

    public function getExpireDateTime(): \DateTime
    {
        return $this->expireDateTime;
    }

    protected function setAccessToken(string $accessToken): void
    {
        $this->accessToken = $accessToken;
    }

    protected function setRefreshToken(string $refreshToken): void
    {
        $this->refreshToken = $refreshToken;
    }

    protected function setExpiresIn(int $expiresIn): void
    {
        $this->expiresIn = $expiresIn;
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
