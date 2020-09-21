<?php declare(strict_types=1);
/*
 * (c) shopware AG <info@shopware.com>
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Swag\PayPal\Pos\Api\Authentification\ApiKey\Payload;

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

    protected function setUserType(string $userType): void
    {
        $this->userType = $userType;
    }

    protected function setUuid(string $uuid): void
    {
        $this->uuid = $uuid;
    }

    protected function setOrgUuid(string $orgUuid): void
    {
        $this->orgUuid = $orgUuid;
    }

    protected function setUserRole(string $userRole): void
    {
        $this->userRole = $userRole;
    }
}
