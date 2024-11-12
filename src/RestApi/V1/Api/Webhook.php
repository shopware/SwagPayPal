<?php declare(strict_types=1);
/*
 * (c) shopware AG <info@shopware.com>
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Swag\PayPal\RestApi\V1\Api;

use OpenApi\Attributes as OA;
use Shopware\Core\Framework\Log\Package;
use Swag\PayPal\RestApi\PayPalApiStruct;
use Swag\PayPal\RestApi\V1\Api\Common\Link;
use Swag\PayPal\RestApi\V1\Api\Common\LinkCollection;
use Swag\PayPal\RestApi\V1\Api\Webhook\Resource;
use Swag\PayPal\RestApi\V2\Api\Order\PurchaseUnit\Payments\Authorization;
use Swag\PayPal\RestApi\V2\Api\Order\PurchaseUnit\Payments\Capture;
use Swag\PayPal\RestApi\V2\Api\Order\PurchaseUnit\Payments\Refund;
use Swag\PayPal\RestApi\V3\Api\PaymentToken;

#[OA\Schema(schema: 'swag_paypal_v1_webhook')]
#[Package('checkout')]
class Webhook extends PayPalApiStruct
{
    public const RESOURCE_TYPE_AUTHORIZATION = 'authorization';
    public const RESOURCE_TYPE_CAPTURE = 'capture';
    public const RESOURCE_TYPE_REFUND = 'refund';
    public const RESOURCE_TYPE_PAYMENT_TOKEN = 'payment_token';
    public const RESOURCE_TYPE_SUBSCRIPTION = 'subscription';

    #[OA\Property(type: 'string')]
    protected string $id;

    #[OA\Property(type: 'string')]
    protected string $resourceType = '';

    #[OA\Property(type: 'string')]
    protected string $eventType;

    #[OA\Property(type: 'string')]
    protected string $summary;

    #[OA\Property(nullable: true, oneOf: [
        new OA\Schema(ref: PaymentToken::class),
        new OA\Schema(ref: Authorization::class),
        new OA\Schema(ref: Capture::class),
        new OA\Schema(ref: Refund::class),
        new OA\Schema(ref: Resource::class),
        new OA\Schema(ref: Subscription::class),
    ])]
    protected ?PayPalApiStruct $resource = null;

    #[OA\Property(type: 'string')]
    protected string $createTime;

    #[OA\Property(type: 'array', items: new OA\Items(ref: Link::class))]
    protected LinkCollection $links;

    #[OA\Property(type: 'string')]
    protected string $eventVersion;

    #[OA\Property(type: 'string')]
    protected string $resourceVersion = '1.0';

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

    public function getResource(): ?PayPalApiStruct
    {
        return $this->resource;
    }

    public function setResource(?PayPalApiStruct $resource): void
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

    public function getResourceVersion(): string
    {
        return $this->resourceVersion;
    }

    public function setResourceVersion(string $resourceVersion): void
    {
        $this->resourceVersion = $resourceVersion;
    }

    /**
     * @param array<string, mixed> $arrayDataWithSnakeCaseKeys
     */
    public function assign(array $arrayDataWithSnakeCaseKeys): static
    {
        $resourceData = $arrayDataWithSnakeCaseKeys['resource'] ?? null;
        unset($arrayDataWithSnakeCaseKeys['resource']);
        $webhook = parent::assign($arrayDataWithSnakeCaseKeys);
        if ($resourceData === null) {
            return $webhook;
        }

        $resourceClass = $this->identifyResourceType($this->resourceVersion, $this->resourceType);
        if ($resourceClass === null) {
            return $webhook;
        }

        $resource = new $resourceClass();
        $resource->assign($resourceData);
        $webhook->setResource($resource);

        return $webhook;
    }

    /**
     * @return class-string<PayPalApiStruct>|null
     */
    protected function identifyResourceType(string $resourceVersion, string $resourceType): ?string
    {
        return match ($resourceVersion) {
            '3.0' => match ($resourceType) {
                self::RESOURCE_TYPE_PAYMENT_TOKEN => PaymentToken::class,
                default => null,
            },
            '2.0' => match ($resourceType) {
                self::RESOURCE_TYPE_AUTHORIZATION => Authorization::class,
                self::RESOURCE_TYPE_CAPTURE => Capture::class,
                self::RESOURCE_TYPE_REFUND => Refund::class,
                self::RESOURCE_TYPE_SUBSCRIPTION => Subscription::class,
                default => null,
            },
            default => Resource::class,
        };
    }
}
