<?php declare(strict_types=1);
/*
 * (c) shopware AG <info@shopware.com>
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Swag\PayPal\IZettle\Api\Webhook;

use Swag\PayPal\IZettle\Api\Common\IZettleStruct;

class Webhook extends IZettleStruct
{
    /**
     * @var string
     */
    protected $organizationUuid;

    /**
     * @var string
     */
    protected $messageUuid;

    /**
     * @var string
     */
    protected $eventName;

    /**
     * @var string
     */
    protected $payload;

    /**
     * @var string
     */
    protected $timestamp;

    public function getEventName(): string
    {
        return $this->eventName;
    }

    public function getPayload(): string
    {
        return $this->payload;
    }

    public function getTimestamp(): string
    {
        return $this->timestamp;
    }

    protected function setOrganizationUuid(string $organizationUuid): void
    {
        $this->organizationUuid = $organizationUuid;
    }

    protected function setMessageUuid(string $messageUuid): void
    {
        $this->messageUuid = $messageUuid;
    }

    protected function setEventName(string $eventName): void
    {
        $this->eventName = $eventName;
    }

    protected function setPayload(string $payload): void
    {
        $this->payload = $payload;
    }

    protected function setTimestamp(string $timestamp): void
    {
        $this->timestamp = $timestamp;
    }
}
