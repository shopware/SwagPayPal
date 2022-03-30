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
 * @OA\Schema(schema="swag_paypal_v1_client_token")
 */
class ClientToken extends PayPalApiStruct
{
    /**
     * @OA\Property(type="string")
     */
    protected string $clientToken;

    /**
     * The lifetime of the access token, in seconds.
     *
     * @OA\Property(type="integer")
     */
    protected int $expiresIn;

    /**
     * Calculated expiration date
     *
     * @OA\Property(type="date")
     */
    protected \DateTime $expireDateTime;

    /**
     * @return static
     */
    public function assign(array $arrayDataWithSnakeCaseKeys)
    {
        $newToken = parent::assign($arrayDataWithSnakeCaseKeys);

        //Calculate the expiration date manually
        $expirationDateTime = new \DateTime();
        $interval = \DateInterval::createFromDateString(\sprintf('%s seconds', $newToken->getExpiresIn()));
        $expirationDateTime = $expirationDateTime->add($interval);

        $newToken->setExpireDateTime($expirationDateTime);

        return $newToken;
    }

    public function getClientToken(): string
    {
        return $this->clientToken;
    }

    public function setClientToken(string $clientToken): void
    {
        $this->clientToken = $clientToken;
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
