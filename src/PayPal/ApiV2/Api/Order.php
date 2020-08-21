<?php declare(strict_types=1);
/*
 * (c) shopware AG <info@shopware.com>
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Swag\PayPal\PayPal\ApiV2\Api;

use Swag\PayPal\PayPal\ApiV2\Api\Order\ApplicationContext;
use Swag\PayPal\PayPal\ApiV2\Api\Order\Link;
use Swag\PayPal\PayPal\ApiV2\Api\Order\Payer;
use Swag\PayPal\PayPal\ApiV2\Api\Order\PurchaseUnit;
use Swag\PayPal\PayPal\PayPalApiStruct;

class Order extends PayPalApiStruct
{
    /**
     * @var string
     */
    protected $createTime;

    /**
     * @var string
     */
    protected $updateTime;

    /**
     * @var string
     */
    protected $id;

    /**
     * @var string
     */
    protected $intent;

    /**
     * @var Payer
     */
    protected $payer;

    /**
     * @var PurchaseUnit[]
     */
    protected $purchaseUnits;

    /**
     * @var ApplicationContext
     */
    protected $applicationContext;

    /**
     * @var string
     */
    protected $status;

    /**
     * @var Link[]
     */
    protected $links;

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

    public function getId(): string
    {
        return $this->id;
    }

    public function setId(string $id): void
    {
        $this->id = $id;
    }

    public function getIntent(): string
    {
        return $this->intent;
    }

    public function setIntent(string $intent): void
    {
        $this->intent = $intent;
    }

    public function getPayer(): Payer
    {
        return $this->payer;
    }

    public function setPayer(Payer $payer): void
    {
        $this->payer = $payer;
    }

    /**
     * @return PurchaseUnit[]
     */
    public function getPurchaseUnits(): array
    {
        return $this->purchaseUnits;
    }

    /**
     * @param PurchaseUnit[] $purchaseUnits
     */
    public function setPurchaseUnits(array $purchaseUnits): void
    {
        $this->purchaseUnits = $purchaseUnits;
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

    /**
     * @return Link[]
     */
    public function getLinks(): array
    {
        return $this->links;
    }

    /**
     * @param Link[] $links
     */
    public function setLinks(array $links): void
    {
        $this->links = $links;
    }
}
