<?php declare(strict_types=1);
/*
 * (c) shopware AG <info@shopware.com>
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Swag\PayPal\RestApi\V2\Api\Order\PurchaseUnit\Payments;

use Swag\PayPal\RestApi\PayPalApiStruct;
use Swag\PayPal\RestApi\V2\Api\Order\PurchaseUnit\Payments\Authorization\Amount;
use Swag\PayPal\RestApi\V2\Api\Order\PurchaseUnit\Payments\Authorization\Link;
use Swag\PayPal\RestApi\V2\Api\Order\PurchaseUnit\Payments\Authorization\SellerProtection;

class Authorization extends PayPalApiStruct
{
    /**
     * @var string
     */
    protected $status;

    /**
     * @var string
     */
    protected $id;

    /**
     * @var Amount
     */
    protected $amount;

    /**
     * @var SellerProtection
     */
    protected $sellerProtection;

    /**
     * @var string
     */
    protected $expirationTime;

    /**
     * @var Link[]
     */
    protected $links;

    /**
     * @var string
     */
    protected $createTime;

    /**
     * @var string
     */
    protected $updateTime;

    public function getStatus(): string
    {
        return $this->status;
    }

    public function setStatus(string $status): void
    {
        $this->status = $status;
    }

    public function getId(): string
    {
        return $this->id;
    }

    public function setId(string $id): void
    {
        $this->id = $id;
    }

    public function getAmount(): Amount
    {
        return $this->amount;
    }

    public function setAmount(Amount $amount): void
    {
        $this->amount = $amount;
    }

    public function getSellerProtection(): SellerProtection
    {
        return $this->sellerProtection;
    }

    public function setSellerProtection(SellerProtection $sellerProtection): void
    {
        $this->sellerProtection = $sellerProtection;
    }

    public function getExpirationTime(): string
    {
        return $this->expirationTime;
    }

    public function setExpirationTime(string $expirationTime): void
    {
        $this->expirationTime = $expirationTime;
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
}
