<?php declare(strict_types=1);
/*
 * (c) shopware AG <info@shopware.com>
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Swag\PayPal\Pos\Api\Authentication\ApiKey;

use Swag\PayPal\Pos\Api\Authentication\ApiKey\Payload\User;
use Swag\PayPal\Pos\Api\Common\PosStruct;

/**
 * JWT Payload
 */
class Payload extends PosStruct
{
    /**
     * Issuer
     *
     * @var string
     */
    private $iss;

    /**
     * Audience
     *
     * @var string
     */
    private $aud;

    /**
     * Expiration time
     *
     * @var int
     */
    private $exp;

    /**
     * Subject
     *
     * @var string
     */
    private $sub;

    /**
     * Issued At
     *
     * @var int
     */
    private $iat;

    /**
     * @var bool
     */
    private $renewed;

    /**
     * @var string[]
     */
    private $scope;

    /**
     * @var User
     */
    private $user;

    /**
     * @var string
     */
    private $type;

    /**
     * @var string
     */
    private $clientId;

    public function getClientId(): string
    {
        return $this->clientId;
    }

    protected function setIss(string $iss): void
    {
        $this->iss = $iss;
    }

    protected function setAud(string $aud): void
    {
        $this->aud = $aud;
    }

    protected function setExp(int $exp): void
    {
        $this->exp = $exp;
    }

    protected function setSub(string $sub): void
    {
        $this->sub = $sub;
    }

    protected function setIat(int $iat): void
    {
        $this->iat = $iat;
    }

    protected function setRenewed(bool $renewed): void
    {
        $this->renewed = $renewed;
    }

    protected function setScope(array $scope): void
    {
        $this->scope = $scope;
    }

    protected function setUser(User $user): void
    {
        $this->user = $user;
    }

    protected function setType(string $type): void
    {
        $this->type = $type;
    }

    protected function setClientId(string $clientId): void
    {
        $this->clientId = $clientId;
    }
}
