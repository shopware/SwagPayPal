<?php declare(strict_types=1);

namespace Swag\PayPal\PayPal\Api;

use Swag\PayPal\PayPal\Api\Common\PayPalStruct;
use Swag\PayPal\PayPal\Api\CreateWebhooks\EventType;

class CreateWebhooks extends PayPalStruct
{
    /**
     * @var string
     */
    protected $url;

    /**
     * @var EventType[]
     */
    protected $eventTypes;

    protected function setUrl(string $url): void
    {
        $this->url = $url;
    }

    /**
     * @param EventType[] $eventTypes
     */
    protected function setEventTypes(array $eventTypes): void
    {
        $this->eventTypes = $eventTypes;
    }
}
