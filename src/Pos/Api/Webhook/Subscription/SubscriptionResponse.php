<?php declare(strict_types=1);
/*
 * (c) shopware AG <info@shopware.com>
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Swag\PayPal\Pos\Api\Webhook\Subscription;

use Swag\PayPal\Pos\Api\Common\PosStruct;

class SubscriptionResponse extends PosStruct
{
    /**
     * @var string
     */
    protected $uuid;

    /**
     * @var string
     */
    protected $transportName;

    /**
     * @var string[]
     */
    protected $eventNames;

    /**
     * @var string
     */
    protected $updated;

    /**
     * @var string
     */
    protected $destination;

    /**
     * @var string
     */
    protected $contactEmail;

    /**
     * @var string
     */
    protected $status;

    /**
     * @var string
     */
    protected $signingKey;

    /**
     * @var string
     */
    protected $clientId;

    public function getUuid(): string
    {
        return $this->uuid;
    }

    public function setUuid(string $uuid): void
    {
        $this->uuid = $uuid;
    }

    public function getTransportName(): string
    {
        return $this->transportName;
    }

    public function setTransportName(string $transportName): void
    {
        $this->transportName = $transportName;
    }

    public function getEventNames(): array
    {
        return $this->eventNames;
    }

    public function setEventNames(array $eventNames): void
    {
        $this->eventNames = $eventNames;
    }

    public function getUpdated(): string
    {
        return $this->updated;
    }

    public function setUpdated(string $updated): void
    {
        $this->updated = $updated;
    }

    public function getDestination(): string
    {
        return $this->destination;
    }

    public function setDestination(string $destination): void
    {
        $this->destination = $destination;
    }

    public function getContactEmail(): string
    {
        return $this->contactEmail;
    }

    public function setContactEmail(string $contactEmail): void
    {
        $this->contactEmail = $contactEmail;
    }

    public function getStatus(): string
    {
        return $this->status;
    }

    public function setStatus(string $status): void
    {
        $this->status = $status;
    }

    public function getSigningKey(): string
    {
        return $this->signingKey;
    }

    public function setSigningKey(string $signingKey): void
    {
        $this->signingKey = $signingKey;
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
