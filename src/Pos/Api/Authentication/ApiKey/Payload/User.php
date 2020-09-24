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
     * @var string
     */
    private $userType;

    /**
     * @var string
     */
    private $uuid;

    /**
     * @var string
     */
    private $orgUuid;

    /**
     * @var string
     */
    private $userRole;

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
