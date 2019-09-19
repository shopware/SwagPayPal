<?php declare(strict_types=1);

namespace Swag\PayPal\PayPal\Api;

use Swag\PayPal\PayPal\Api\Common\PayPalStruct;
use Swag\PayPal\PayPal\Api\Webhook\Link;

class Webhook extends PayPalStruct
{
    /**
     * @var string
     */
    protected $id;

    /**
     * @var string
     */
    protected $resourceType;

    /**
     * @var string
     */
    protected $eventType;

    /**
     * @var string
     */
    protected $summary;

    /**
     * @var Webhook\Resource
     */
    protected $resource;

    /**
     * @var string
     */
    private $createTime;

    /**
     * @var Link[]
     */
    private $links;

    /**
     * @var string
     */
    private $eventVersion;

    public function getEventType(): string
    {
        return $this->eventType;
    }

    public function getResource(): Webhook\Resource
    {
        return $this->resource;
    }

    protected function setId(string $id): void
    {
        $this->id = $id;
    }

    protected function setCreateTime(string $createTime): void
    {
        $this->createTime = $createTime;
    }

    protected function setResourceType(string $resourceType): void
    {
        $this->resourceType = $resourceType;
    }

    protected function setEventType(string $eventType): void
    {
        $this->eventType = $eventType;
    }

    protected function setSummary(string $summary): void
    {
        $this->summary = $summary;
    }

    protected function setResource(Webhook\Resource $resource): void
    {
        $this->resource = $resource;
    }

    /**
     * @param Link[] $links
     */
    protected function setLinks(array $links): void
    {
        $this->links = $links;
    }

    protected function setEventVersion(string $eventVersion): void
    {
        $this->eventVersion = $eventVersion;
    }
}
