<?php declare(strict_types=1);
/*
 * (c) shopware AG <info@shopware.com>
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Swag\PayPal\IZettle\Api\Webhook\Payload\AbstractPayload;

use Swag\PayPal\IZettle\Api\Common\IZettleStruct;

abstract class AbstractUpdated extends IZettleStruct
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
