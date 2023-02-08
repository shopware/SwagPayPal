<?php declare(strict_types=1);
/*
 * (c) shopware AG <info@shopware.com>
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Swag\PayPal\RestApi\V1\Api;

use OpenApi\Annotations as OA;
use Swag\PayPal\RestApi\PayPalApiStruct;
use Swag\PayPal\RestApi\V1\Api\Subscription\ApplicationContext;
use Swag\PayPal\RestApi\V1\Api\Subscription\BillingInfo;
use Swag\PayPal\RestApi\V1\Api\Subscription\Link;
use Swag\PayPal\RestApi\V1\Api\Subscription\ShippingAmount;
use Swag\PayPal\RestApi\V1\Api\Subscription\Subscriber;

/**
 * @OA\Schema(schema="swag_paypal_v1_subscription")
 *
 * @codeCoverageIgnore
 *
 * @experimental
 *
 * This class is experimental and not officially supported.
 * It is currently not used within the plugin itself. Use with caution.
 */
class Subscription extends PayPalApiStruct
{
    /**
     * @OA\Property(type="string")
     */
    protected string $id;

    /**
     * @OA\Property(type="string")
     */
    protected string $planId;

    /**
     * @OA\Property(type="string")
     */
    protected string $startTime;

    /**
     * @OA\Property(type="string")
     */
    protected string $quantity;

    /**
     * @OA\Property(ref="#/components/schemas/swag_paypal_v1_common_money")
     */
    protected ShippingAmount $shippingAmount;

    /**
     * @OA\Property(ref="#/components/schemas/swag_paypal_v1_subscription_subscriber")
     */
    protected Subscriber $subscriber;

    /**
     * @OA\Property(ref="#/components/schemas/swag_paypal_v1_subscription_billing_info", nullable=true)
     */
    protected ?BillingInfo $billingInfo = null;

    /**
     * @OA\Property(ref="#/components/schemas/swag_paypal_v1_subscription_application_context")
     */
    protected ApplicationContext $applicationContext;

    /**
     * @OA\Property(type="string")
     */
    protected string $status;

    /**
     * @OA\Property(type="string")
     */
    protected string $statusUpdateTime;

    /**
     * @OA\Property(type="string")
     */
    protected string $createTime;

    /**
     * @OA\Property(type="string")
     */
    protected string $updateTime;

    /**
     * @var Link[]
     *
     * @OA\Property(type="array", items={"$ref": "#/components/schemas/swag_paypal_v1_common_link"})
     */
    protected array $links;

    public function getId(): string
    {
        return $this->id;
    }

    public function setId(string $id): void
    {
        $this->id = $id;
    }

    public function getPlanId(): string
    {
        return $this->planId;
    }

    public function setPlanId(string $planId): void
    {
        $this->planId = $planId;
    }

    public function getStartTime(): string
    {
        return $this->startTime;
    }

    public function setStartTime(string $startTime): void
    {
        $this->startTime = $startTime;
    }

    public function getQuantity(): string
    {
        return $this->quantity;
    }

    public function setQuantity(string $quantity): void
    {
        $this->quantity = $quantity;
    }

    public function getShippingAmount(): ShippingAmount
    {
        return $this->shippingAmount;
    }

    public function setShippingAmount(ShippingAmount $shippingAmount): void
    {
        $this->shippingAmount = $shippingAmount;
    }

    public function getSubscriber(): Subscriber
    {
        return $this->subscriber;
    }

    public function setSubscriber(Subscriber $subscriber): void
    {
        $this->subscriber = $subscriber;
    }

    public function getApplicationContext(): ApplicationContext
    {
        return $this->applicationContext;
    }

    public function setApplicationContext(ApplicationContext $applicationContext): void
    {
        $this->applicationContext = $applicationContext;
    }

    public function getStatus(): string
    {
        return $this->status;
    }

    public function setStatus(string $status): void
    {
        $this->status = $status;
    }

    public function getStatusUpdateTime(): string
    {
        return $this->statusUpdateTime;
    }

    public function setStatusUpdateTime(string $statusUpdateTime): void
    {
        $this->statusUpdateTime = $statusUpdateTime;
    }

    public function getCreateTime(): string
    {
        return $this->createTime;
    }

    public function setCreateTime(string $createTime): void
    {
        $this->createTime = $createTime;
    }

    public function getUpdateTime(): string
    {
        return $this->updateTime;
    }

    public function setUpdateTime(string $updateTime): void
    {
        $this->updateTime = $updateTime;
    }

    public function getLinks(): array
    {
        return $this->links;
    }

    public function setLinks(array $links): void
    {
        $this->links = $links;
    }

    public function getBillingInfo(): ?BillingInfo
    {
        return $this->billingInfo;
    }

    public function setBillingInfo(?BillingInfo $billingInfo): void
    {
        $this->billingInfo = $billingInfo;
    }
}
