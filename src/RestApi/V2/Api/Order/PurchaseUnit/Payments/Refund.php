<?php declare(strict_types=1);
/*
 * (c) shopware AG <info@shopware.com>
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Swag\PayPal\RestApi\V2\Api\Order\PurchaseUnit\Payments;

use Swag\PayPal\RestApi\PayPalApiStruct;
use Swag\PayPal\RestApi\V2\Api\Order\PurchaseUnit\Payments\Refund\Amount;
use Swag\PayPal\RestApi\V2\Api\Order\PurchaseUnit\Payments\Refund\Link;
use Swag\PayPal\RestApi\V2\Api\Order\PurchaseUnit\Payments\Refund\SellerPayableBreakdown;

class Refund extends PayPalApiStruct
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
     * @var Amount|null
     */
    protected $amount;

    /**
     * @var string|null
     */
    protected $invoiceId;

    /**
     * @var string|null
     */
    protected $noteToPayer;

    /**
     * @var SellerPayableBreakdown
     */
    protected $sellerPayableBreakdown;

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

    public function getAmount(): ?Amount
    {
        return $this->amount;
    }

    public function setAmount(?Amount $amount): void
    {
        $this->amount = $amount;
    }

    public function getInvoiceId(): ?string
    {
        return $this->invoiceId;
    }

    public function setInvoiceId(?string $invoiceId): void
    {
        $this->invoiceId = $invoiceId;
    }

    public function getNoteToPayer(): ?string
    {
        return $this->noteToPayer;
    }

    public function setNoteToPayer(?string $noteToPayer): void
    {
        $this->noteToPayer = $noteToPayer;
    }

    public function getSellerPayableBreakdown(): SellerPayableBreakdown
    {
        return $this->sellerPayableBreakdown;
    }

    public function setSellerPayableBreakdown(SellerPayableBreakdown $sellerPayableBreakdown): void
    {
        $this->sellerPayableBreakdown = $sellerPayableBreakdown;
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
