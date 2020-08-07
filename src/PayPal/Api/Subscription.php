<?php
declare(strict_types=1);
/*
 * (c) shopware AG <info@shopware.com>
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Swag\PayPal\PayPal\Api;

use Swag\PayPal\PayPal\Api\Common\PayPalStruct;
use Swag\PayPal\PayPal\Api\Subscription\ApplicationContext;
use Swag\PayPal\PayPal\Api\Subscription\BillingInfo;
use Swag\PayPal\PayPal\Api\Subscription\Link;
use Swag\PayPal\PayPal\Api\Subscription\ShippingAmount;
use Swag\PayPal\PayPal\Api\Subscription\Subscriber;

class Subscription extends PayPalStruct
{
    /** @var string */
    protected $id;

    /** @var string */
    protected $planId;

    /** @var string */
    protected $startTime;

    /** @var string */
    protected $quantity;

    /** @var ShippingAmount */
    protected $shippingAmount;

    /** @var Subscriber */
    protected $subscriber;

    /** @var ?BillingInfo */
    protected $billingInfo;

    /** @var ApplicationContext */
    protected $applicationContext;

    /** @var string */
    protected $status;

    /** @var string */
    protected $statusUpdateTime;

    /** @var string */
    protected $createTime;

    /** @var string */
    protected $updateTime;

    /** @var Link[] */
    protected $links;

    public function getId(): string
    {
        return $this->id;
    }

    public function setId(string $id): self
    {
        $this->id = $id;

        return $this;
    }

    public function getPlanId(): string
    {
        return $this->planId;
    }

    public function setPlanId(string $planId): self
    {
        $this->planId = $planId;

        return $this;
    }

    public function getStartTime(): string
    {
        return $this->startTime;
    }

    public function setStartTime(string $startTime): self
    {
        $this->startTime = $startTime;

        return $this;
    }

    public function getQuantity(): string
    {
        return $this->quantity;
    }

    public function setQuantity(string $quantity): self
    {
        $this->quantity = $quantity;

        return $this;
    }

    public function getShippingAmount(): ShippingAmount
    {
        return $this->shippingAmount;
    }

    public function setShippingAmount(ShippingAmount $shippingAmount): self
    {
        $this->shippingAmount = $shippingAmount;

        return $this;
    }

    public function getSubscriber(): Subscriber
    {
        return $this->subscriber;
    }

    public function setSubscriber(Subscriber $subscriber): self
    {
        $this->subscriber = $subscriber;

        return $this;
    }

    public function getApplicationContext(): ApplicationContext
    {
        return $this->applicationContext;
    }

    public function setApplicationContext(ApplicationContext $applicationContext): self
    {
        $this->applicationContext = $applicationContext;

        return $this;
    }

    public function getStatus(): string
    {
        return $this->status;
    }

    public function setStatus(string $status): self
    {
        $this->status = $status;

        return $this;
    }

    public function getStatusUpdateTime(): string
    {
        return $this->statusUpdateTime;
    }

    public function setStatusUpdateTime(string $statusUpdateTime): self
    {
        $this->statusUpdateTime = $statusUpdateTime;

        return $this;
    }

    public function getCreateTime(): string
    {
        return $this->createTime;
    }

    public function setCreateTime(string $createTime): self
    {
        $this->createTime = $createTime;

        return $this;
    }

    public function getUpdateTime(): string
    {
        return $this->updateTime;
    }

    public function setUpdateTime(string $updateTime): self
    {
        $this->updateTime = $updateTime;

        return $this;
    }

    public function getLinks(): array
    {
        return $this->links;
    }

    public function setLinks(array $links): self
    {
        $this->links = $links;

        return $this;
    }

    public function getBillingInfo(): ?BillingInfo
    {
        return $this->billingInfo;
    }

    public function setBillingInfo(?BillingInfo $billingInfo): self
    {
        $this->billingInfo = $billingInfo;

        return $this;
    }
}
