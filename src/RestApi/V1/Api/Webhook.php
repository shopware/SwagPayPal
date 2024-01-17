<?php

declare(strict_types=1);
/*
 * (c) shopware AG <info@shopware.com>
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Swag\PayPal\RestApi\V1\Api;

use OpenApi\Annotations as OA;
use Shopware\Core\Framework\Log\Package;
use Swag\PayPal\RestApi\PayPalApiStruct;
use Swag\PayPal\RestApi\V1\Api\Common\LinkCollection;
use Swag\PayPal\RestApi\V1\Api\Webhook\Resource;

/**
 * @OA\Schema(schema="swag_paypal_v1_webhook")
 */
#[Package('checkout')]
class Webhook extends PayPalApiStruct
{
    /**
     * @OA\Property(type="string")
     */
    protected string $id;

    /**
     * @OA\Property(type="string")
     */
    protected string $resourceType;

    /**
     * @OA\Property(type="string")
     */
    protected string $eventType;

    /**
     * @OA\Property(type="string")
     */
    protected string $summary;

    /**
     * @OA\Property(ref="#/components/schemas/swag_paypal_v1_webhook_resource")
     */
    protected Resource $resource;

    /**
     * @OA\Property(type="string")
     */
    protected string $createTime;

    /**
     * @OA\Property(type="array", items={"$ref": "#/components/schemas/swag_paypal_v1_common_link"})
     */
    protected LinkCollection $links;

    /**
     * @OA\Property(type="string")
     */
    protected string $eventVersion;

    public function getId(): string
    {
        return $this->id;
    }

    public function setId(string $id): void
    {
        $this->id = $id;
    }

    public function getResourceType(): string
    {
        return $this->resourceType;
    }

    public function setResourceType(string $resourceType): void
    {
        $this->resourceType = $resourceType;
    }

    public function getEventType(): string
    {
        return $this->eventType;
    }

    public function setEventType(string $eventType): void
    {
        $this->eventType = $eventType;
    }

    public function getSummary(): string
    {
        return $this->summary;
    }

    public function setSummary(string $summary): void
    {
        $this->summary = $summary;
    }

    public function getResource(): Resource
    {
        return $this->resource;
    }

    public function setResource(Resource $resource): void
    {
        $this->resource = $resource;
    }

    public function getCreateTime(): string
    {
        return $this->createTime;
    }

    public function setCreateTime(string $createTime): void
    {
        $this->createTime = $createTime;
    }

    public function getLinks(): LinkCollection
    {
        return $this->links;
    }

    public function setLinks(LinkCollection $links): void
    {
        $this->links = $links;
    }

    public function getEventVersion(): string
    {
        return $this->eventVersion;
    }

    public function setEventVersion(string $eventVersion): void
    {
        $this->eventVersion = $eventVersion;
    }
}
