<?php declare(strict_types=1);
/*
 * (c) shopware AG <info@shopware.com>
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Swag\PayPal\RestApi\V2\Api;

use OpenApi\Annotations as OA;
use Shopware\Core\Framework\Log\Package;
use Swag\PayPal\RestApi\PayPalApiStruct;
use Swag\PayPal\RestApi\V2\Api\Common\LinkCollection;
use Swag\PayPal\RestApi\V2\Api\Order\PurchaseUnit\Payments\Authorization;
use Swag\PayPal\RestApi\V2\Api\Order\PurchaseUnit\Payments\Capture;
use Swag\PayPal\RestApi\V2\Api\Order\PurchaseUnit\Payments\Payment;
use Swag\PayPal\RestApi\V2\Api\Order\PurchaseUnit\Payments\Refund;
use Swag\PayPal\RestApi\V2\Api\Webhook\Link;

/**
 * @OA\Schema(schema="swag_paypal_v2_webhook")
 */
#[Package('checkout')]
class Webhook extends PayPalApiStruct
{
    public const RESOURCE_TYPE_AUTHORIZATION = 'authorization';
    public const RESOURCE_TYPE_CAPTURE = 'capture';
    public const RESOURCE_TYPE_REFUND = 'refund';

    /**
     * @OA\Property(type="string")
     */
    protected string $id;

    /**
     * @OA\Property(type="string")
     */
    protected string $createTime;

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
     * @OA\Property(
     *  oneOf={
     *
     *      @OA\Schema(ref="#/components/schemas/swag_paypal_v2_order_authorization"},
     *      @OA\Schema(ref:"#/components/schemas/swag_paypal_v2_order_capture"},
     *      @OA\Schema(ref":"#/components/schemas/swag_paypal_v2_order_refund"},
     *  },
     *  nullable=true
     * )
     */
    protected ?Payment $resource;

    /**
     * @OA\Property(type="array", items={"$ref": "#/components/schemas/swag_paypal_v2_common_link"})
     */
    protected LinkCollection $links;

    /**
     * @OA\Property(type="string")
     */
    protected string $eventVersion;

    /**
     * @OA\Property(type="string")
     */
    protected string $resourceVersion;

    /**
     * @param array<string, mixed> $arrayDataWithSnakeCaseKeys
     */
    public function assign(array $arrayDataWithSnakeCaseKeys): static
    {
        $resourceData = $arrayDataWithSnakeCaseKeys['resource'];
        unset($arrayDataWithSnakeCaseKeys['resource']);
        $webhook = parent::assign($arrayDataWithSnakeCaseKeys);

        $resourceClass = $this->identifyResourceType($arrayDataWithSnakeCaseKeys['resource_type']);
        if ($resourceClass === null) {
            $webhook->setResource(null);

            return $webhook;
        }

        $resource = new $resourceClass();
        $resource->assign($resourceData);
        $webhook->setResource($resource);

        return $webhook;
    }

    public function getId(): string
    {
        return $this->id;
    }

    public function setId(string $id): void
    {
        $this->id = $id;
    }

    public function getCreateTime(): string
    {
        return $this->createTime;
    }

    public function setCreateTime(string $createTime): void
    {
        $this->createTime = $createTime;
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

    public function getResource(): ?Payment
    {
        return $this->resource;
    }

    public function setResource(?Payment $resource): void
    {
        $this->resource = $resource;
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

    public function getResourceVersion(): string
    {
        return $this->resourceVersion;
    }

    public function setResourceVersion(string $resourceVersion): void
    {
        $this->resourceVersion = $resourceVersion;
    }

    /**
     * @return class-string<Payment>|null
     */
    protected function identifyResourceType(string $resourceType): ?string
    {
        switch ($resourceType) {
            case self::RESOURCE_TYPE_AUTHORIZATION:
                return Authorization::class;
            case self::RESOURCE_TYPE_CAPTURE:
                return Capture::class;
            case self::RESOURCE_TYPE_REFUND:
                return Refund::class;
            default:
                return null;
        }
    }
}
