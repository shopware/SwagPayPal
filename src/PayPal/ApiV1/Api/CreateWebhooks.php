<?php declare(strict_types=1);
/*
 * (c) shopware AG <info@shopware.com>
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Swag\PayPal\PayPal\ApiV1\Api;

use Swag\PayPal\PayPal\ApiV1\Api\CreateWebhooks\EventType;
use Swag\PayPal\PayPal\PayPalApiStruct;

class CreateWebhooks extends PayPalApiStruct
{
    /**
     * @var string
     */
    protected $url;

    /**
     * @var EventType[]
     */
    protected $eventTypes;

    public function getUrl(): string
    {
        return $this->url;
    }

    public function setUrl(string $url): void
    {
        $this->url = $url;
    }

    /**
     * @return EventType[]
     */
    public function getEventTypes(): array
    {
        return $this->eventTypes;
    }

    /**
     * @param EventType[] $eventTypes
     */
    public function setEventTypes(array $eventTypes): void
    {
        $this->eventTypes = $eventTypes;
    }
}
