<?php declare(strict_types=1);
/*
 * (c) shopware AG <info@shopware.com>
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Swag\PayPal\RestApi\V1\Api;

use OpenApi\Annotations as OA;
use Swag\PayPal\RestApi\PayPalApiStruct;
use Swag\PayPal\RestApi\V1\Api\CreateWebhooks\EventType;

/**
 * @OA\Schema(schema="swag_paypal_v1_create_webhooks")
 */
class CreateWebhooks extends PayPalApiStruct
{
    /**
     * @OA\Property(type="string")
     */
    protected string $url;

    /**
     * @var EventType[]
     * @OA\Property(type="array", items={"$ref": "#/components/schemas/swag_paypal_v1_create_webhooks_event_type"})
     */
    protected array $eventTypes;

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
