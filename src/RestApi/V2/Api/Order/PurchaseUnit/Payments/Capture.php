<?php declare(strict_types=1);
/*
 * (c) shopware AG <info@shopware.com>
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Swag\PayPal\RestApi\V2\Api\Order\PurchaseUnit\Payments;

use Swag\PayPal\RestApi\PayPalApiStruct;
use Swag\PayPal\RestApi\V2\Api\Order\PurchaseUnit\Payments\Capture\Amount;
use Swag\PayPal\RestApi\V2\Api\Order\PurchaseUnit\Payments\Capture\Link;
use Swag\PayPal\RestApi\V2\Api\Order\PurchaseUnit\Payments\Capture\SellerProtection;
use Swag\PayPal\RestApi\V2\Api\Order\PurchaseUnit\Payments\Capture\SellerReceivableBreakdown;

class Capture extends PayPalApiStruct
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
     * @var string
     */
    protected $invoiceId;

    /**
     * @var string
     */
    protected $noteToPayer;

    /**
     * @var SellerProtection
     */
    protected $sellerProtection;

    /**
     * @var bool
     */
    protected $finalCapture;

    /**
     * @var SellerReceivableBreakdown
     */
    protected $sellerReceivableBreakdown;

    /**
     * @var string
     */
    protected $disbursementMode;

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

    public function getInvoiceId(): string
    {
        return $this->invoiceId;
    }

    public function setInvoiceId(string $invoiceId): void
    {
        $this->invoiceId = $invoiceId;
    }

    public function getNoteToPayer(): string
    {
        return $this->noteToPayer;
    }

    public function setNoteToPayer(string $noteToPayer): void
    {
        $this->noteToPayer = $noteToPayer;
    }

    public function getSellerProtection(): SellerProtection
    {
        return $this->sellerProtection;
    }

    public function setSellerProtection(SellerProtection $sellerProtection): void
    {
        $this->sellerProtection = $sellerProtection;
    }

    public function isFinalCapture(): bool
    {
        return $this->finalCapture;
    }

    public function setFinalCapture(bool $finalCapture): void
    {
        $this->finalCapture = $finalCapture;
    }

    public function getSellerReceivableBreakdown(): SellerReceivableBreakdown
    {
        return $this->sellerReceivableBreakdown;
    }

    public function setSellerReceivableBreakdown(SellerReceivableBreakdown $sellerReceivableBreakdown): void
    {
        $this->sellerReceivableBreakdown = $sellerReceivableBreakdown;
    }

    public function getDisbursementMode(): string
    {
        return $this->disbursementMode;
    }

    public function setDisbursementMode(string $disbursementMode): void
    {
        $this->disbursementMode = $disbursementMode;
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
