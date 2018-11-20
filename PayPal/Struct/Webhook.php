<?php declare(strict_types=1);
/**
 * (c) shopware AG <info@shopware.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace SwagPayPal\PayPal\Struct;

class Webhook
{
    /**
     * @var string
     */
    private $id;

    /**
     * @var string
     */
    private $creationTime;

    /**
     * @var string
     */
    private $resourceType;

    /**
     * @var string
     */
    private $eventType;

    /**
     * @var string
     */
    private $summary;

    /**
     * @var array
     */
    private $resource;

    public function getId(): string
    {
        return $this->id;
    }

    public function getCreationTime(): string
    {
        return $this->creationTime;
    }

    public function getResourceType(): string
    {
        return $this->resourceType;
    }

    public function getEventType(): string
    {
        return $this->eventType;
    }

    public function getSummary(): string
    {
        return $this->summary;
    }

    public function getResource(): array
    {
        return $this->resource;
    }

    public function setResource(array $resource): void
    {
        $this->resource = $resource;
    }

    public static function fromArray(array $data): self
    {
        $result = new self();
        $result->setEventType($data['event_type']);
        $result->setCreationTime($data['create_time']);
        $result->setId($data['id']);
        $result->setResourceType($data['resource_type']);
        $result->setSummary($data['summary']);
        $result->setResource($data['resource']);

        return $result;
    }

    /**
     * Converts this object to an array.
     */
    public function toArray(): array
    {
        return get_object_vars($this);
    }

    public function setEventType(string $eventType): void
    {
        $this->eventType = $eventType;
    }

    private function setId(string $id): void
    {
        $this->id = $id;
    }

    private function setCreationTime(string $creationTime): void
    {
        $this->creationTime = $creationTime;
    }

    private function setResourceType(string $resourceType): void
    {
        $this->resourceType = $resourceType;
    }

    private function setSummary(string $summary): void
    {
        $this->summary = $summary;
    }
}
