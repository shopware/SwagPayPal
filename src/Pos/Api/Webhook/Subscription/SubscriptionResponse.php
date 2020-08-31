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
    private $uuid;

    /**
     * @var string
     */
    private $transportName;

    /**
     * @var string[]
     */
    private $eventNames;

    /**
     * @var string
     */
    private $updated;

    /**
     * @var string
     */
    private $destination;

    /**
     * @var string
     */
    private $contactEmail;

    /**
     * @var string
     */
    private $status;

    /**
     * @var string
     */
    private $signingKey;

    /**
     * @var string
     */
    private $clientId;

    public function getSigningKey(): string
    {
        return $this->signingKey;
    }

    protected function setUuid(string $uuid): void
    {
        $this->uuid = $uuid;
    }

    protected function setTransportName(string $transportName): void
    {
        $this->transportName = $transportName;
    }

    protected function setEventNames(array $eventNames): void
    {
        $this->eventNames = $eventNames;
    }

    protected function setUpdated(string $updated): void
    {
        $this->updated = $updated;
    }

    protected function setDestination(string $destination): void
    {
        $this->destination = $destination;
    }

    protected function setContactEmail(string $contactEmail): void
    {
        $this->contactEmail = $contactEmail;
    }

    protected function setStatus(string $status): void
    {
        $this->status = $status;
    }

    protected function setSigningKey(string $signingKey): void
    {
        $this->signingKey = $signingKey;
    }

    protected function setClientId(string $clientId): void
    {
        $this->clientId = $clientId;
    }
}
