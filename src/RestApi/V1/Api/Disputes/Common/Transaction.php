<?php declare(strict_types=1);
/*
 * (c) shopware AG <info@shopware.com>
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Swag\PayPal\RestApi\V1\Api\Disputes\Common;

use Swag\PayPal\RestApi\PayPalApiStruct;
use Swag\PayPal\RestApi\V1\Api\Common\Money;

abstract class Transaction extends PayPalApiStruct
{
    /**
     * @var string
     */
    protected $buyerTransactionId;

    /**
     * @var string
     */
    protected $sellerTransactionId;

    /**
     * @var string
     */
    protected $referenceId;

    /**
     * @var string
     */
    protected $createTime;

    /**
     * @var string
     */
    protected $transactionStatus;

    /**
     * @var Money
     */
    protected $grossAmount;

    /**
     * @var string
     */
    protected $invoiceNumber;

    /**
     * @var string
     */
    protected $custom;

    /**
     * @var Buyer
     */
    protected $buyer;

    /**
     * @var Seller
     */
    protected $seller;

    /**
     * @var Item[]
     */
    protected $items;

    public function getBuyerTransactionId(): string
    {
        return $this->buyerTransactionId;
    }

    public function setBuyerTransactionId(string $buyerTransactionId): void
    {
        $this->buyerTransactionId = $buyerTransactionId;
    }

    public function getSellerTransactionId(): string
    {
        return $this->sellerTransactionId;
    }

    public function setSellerTransactionId(string $sellerTransactionId): void
    {
        $this->sellerTransactionId = $sellerTransactionId;
    }

    public function getReferenceId(): string
    {
        return $this->referenceId;
    }

    public function setReferenceId(string $referenceId): void
    {
        $this->referenceId = $referenceId;
    }

    public function getCreateTime(): string
    {
        return $this->createTime;
    }

    public function setCreateTime(string $createTime): void
    {
        $this->createTime = $createTime;
    }

    public function getTransactionStatus(): string
    {
        return $this->transactionStatus;
    }

    public function setTransactionStatus(string $transactionStatus): void
    {
        $this->transactionStatus = $transactionStatus;
    }

    public function getGrossAmount(): Money
    {
        return $this->grossAmount;
    }

    public function setGrossAmount(Money $grossAmount): void
    {
        $this->grossAmount = $grossAmount;
    }

    public function getInvoiceNumber(): string
    {
        return $this->invoiceNumber;
    }

    public function setInvoiceNumber(string $invoiceNumber): void
    {
        $this->invoiceNumber = $invoiceNumber;
    }

    public function getCustom(): string
    {
        return $this->custom;
    }

    public function setCustom(string $custom): void
    {
        $this->custom = $custom;
    }

    public function getBuyer(): Buyer
    {
        return $this->buyer;
    }

    public function setBuyer(Buyer $buyer): void
    {
        $this->buyer = $buyer;
    }

    public function getSeller(): Seller
    {
        return $this->seller;
    }

    public function setSeller(Seller $seller): void
    {
        $this->seller = $seller;
    }

    /**
     * @return Item[]
     */
    public function getItems(): array
    {
        return $this->items;
    }

    /**
     * @param Item[] $items
     */
    public function setItems(array $items): void
    {
        $this->items = $items;
    }
}
