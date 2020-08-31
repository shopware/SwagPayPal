<?php declare(strict_types=1);
/*
 * (c) shopware AG <info@shopware.com>
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Swag\PayPal\Pos\Api\Webhook\Payload\AbstractPayload;

use Swag\PayPal\Pos\Api\Common\PosStruct;

abstract class AbstractUpdated extends PosStruct
{
    /**
     * @var string
     */
    protected $uuid;

    /**
     * @var string
     */
    protected $timestamp;

    /**
     * @var string
     */
    protected $userType;

    /**
     * @var string
     */
    protected $clientUuid;

    public function getClientUuid(): string
    {
        return $this->clientUuid;
    }

    protected function setUuid(string $uuid): void
    {
        $this->uuid = $uuid;
    }

    protected function setTimestamp(string $timestamp): void
    {
        $this->timestamp = $timestamp;
    }

    protected function setUserType(string $userType): void
    {
        $this->userType = $userType;
    }

    protected function setClientUuid(string $clientUuid): void
    {
        $this->clientUuid = $clientUuid;
    }
}
