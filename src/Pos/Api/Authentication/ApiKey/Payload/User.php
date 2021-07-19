<?php declare(strict_types=1);
/*
 * (c) shopware AG <info@shopware.com>
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Swag\PayPal\Pos\Api\Authentication\ApiKey\Payload;

use Swag\PayPal\Pos\Api\Common\PosStruct;

class User extends PosStruct
{
    /**
     * @deprecated tag:v4.0.0 - will be strongly typed
     *
     * @var string
     */
    protected $userType;

    /**
     * @deprecated tag:v4.0.0 - will be strongly typed
     *
     * @var string
     */
    protected $uuid;

    /**
     * @deprecated tag:v4.0.0 - will be strongly typed
     *
     * @var string
     */
    protected $orgUuid;

    /**
     * @deprecated tag:v4.0.0 - will be strongly typed
     *
     * @var string
     */
    protected $userRole;

    public function getUserType(): string
    {
        return $this->userType;
    }

    public function setUserType(string $userType): void
    {
        $this->userType = $userType;
    }

    public function getUuid(): string
    {
        return $this->uuid;
    }

    public function setUuid(string $uuid): void
    {
        $this->uuid = $uuid;
    }

    public function getOrgUuid(): string
    {
        return $this->orgUuid;
    }

    public function setOrgUuid(string $orgUuid): void
    {
        $this->orgUuid = $orgUuid;
    }

    public function getUserRole(): string
    {
        return $this->userRole;
    }

    public function setUserRole(string $userRole): void
    {
        $this->userRole = $userRole;
    }
}
