<?php declare(strict_types=1);
/*
 * (c) shopware AG <info@shopware.com>
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Swag\PayPal\Pos\Api\Webhook;

use Swag\PayPal\Pos\Api\Common\PosStruct;

class Webhook extends PosStruct
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

    public function getOrganizationUuid(): string
    {
        return $this->organizationUuid;
    }

    public function setOrganizationUuid(string $organizationUuid): void
    {
        $this->organizationUuid = $organizationUuid;
    }

    public function getMessageUuid(): string
    {
        return $this->messageUuid;
    }

    public function setMessageUuid(string $messageUuid): void
    {
        $this->messageUuid = $messageUuid;
    }

    public function getEventName(): string
    {
        return $this->eventName;
    }

    public function setEventName(string $eventName): void
    {
        $this->eventName = $eventName;
    }

    public function getPayload(): string
    {
        return $this->payload;
    }

    public function setPayload(string $payload): void
    {
        $this->payload = $payload;
    }

    public function getTimestamp(): string
    {
        return $this->timestamp;
    }

    public function setTimestamp(string $timestamp): void
    {
        $this->timestamp = $timestamp;
    }
}
