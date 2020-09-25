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
    protected $iss;

    /**
     * Audience
     *
     * @var string
     */
    protected $aud;

    /**
     * Expiration time
     *
     * @var int
     */
    protected $exp;

    /**
     * Subject
     *
     * @var string
     */
    protected $sub;

    /**
     * Issued At
     *
     * @var int
     */
    protected $iat;

    /**
     * @var bool
     */
    protected $renewed;

    /**
     * @var string[]
     */
    protected $scope;

    /**
     * @var User
     */
    protected $user;

    /**
     * @var string
     */
    protected $type;

    /**
     * @var string
     */
    protected $clientId;

    public function getIss(): string
    {
        return $this->iss;
    }

    public function setIss(string $iss): void
    {
        $this->iss = $iss;
    }

    public function getAud(): string
    {
        return $this->aud;
    }

    public function setAud(string $aud): void
    {
        $this->aud = $aud;
    }

    public function getExp(): int
    {
        return $this->exp;
    }

    public function setExp(int $exp): void
    {
        $this->exp = $exp;
    }

    public function getSub(): string
    {
        return $this->sub;
    }

    public function setSub(string $sub): void
    {
        $this->sub = $sub;
    }

    public function getIat(): int
    {
        return $this->iat;
    }

    public function setIat(int $iat): void
    {
        $this->iat = $iat;
    }

    public function isRenewed(): bool
    {
        return $this->renewed;
    }

    public function setRenewed(bool $renewed): void
    {
        $this->renewed = $renewed;
    }

    public function getScope(): array
    {
        return $this->scope;
    }

    public function setScope(array $scope): void
    {
        $this->scope = $scope;
    }

    public function getUser(): User
    {
        return $this->user;
    }

    public function setUser(User $user): void
    {
        $this->user = $user;
    }

    public function getType(): string
    {
        return $this->type;
    }

    public function setType(string $type): void
    {
        $this->type = $type;
    }

    public function getClientId(): string
    {
        return $this->clientId;
    }

    public function setClientId(string $clientId): void
    {
        $this->clientId = $clientId;
    }
}
