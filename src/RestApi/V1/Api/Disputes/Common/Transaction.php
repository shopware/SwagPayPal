<?php declare(strict_types=1);
/*
 * (c) shopware AG <info@shopware.com>
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Swag\PayPal\RestApi\V1\Api\Disputes\Common;

use OpenApi\Attributes as OA;
use Shopware\Core\Framework\Log\Package;
use Swag\PayPal\RestApi\PayPalApiStruct;
use Swag\PayPal\RestApi\V1\Api\Common\Money;

#[OA\Schema(schema: 'swag_paypal_v1_disputes_common_transaction')]
#[Package('checkout')]
class Transaction extends PayPalApiStruct
{
    #[OA\Property(type: 'string')]
    protected string $buyerTransactionId;

    #[OA\Property(type: 'string')]
    protected string $sellerTransactionId;

    #[OA\Property(type: 'string')]
    protected string $referenceId;

    #[OA\Property(type: 'string')]
    protected string $createTime;

    #[OA\Property(type: 'string')]
    protected string $transactionStatus;

    #[OA\Property(ref: Money::class)]
    protected Money $grossAmount;

    #[OA\Property(type: 'string')]
    protected string $invoiceNumber;

    #[OA\Property(type: 'string')]
    protected string $custom;

    #[OA\Property(ref: Buyer::class)]
    protected Buyer $buyer;

    #[OA\Property(ref: Seller::class)]
    protected Seller $seller;

    #[OA\Property(type: 'array', items: new OA\Items(ref: Item::class))]
    protected ItemCollection $items;

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

    public function getItems(): ItemCollection
    {
        return $this->items;
    }

    public function setItems(ItemCollection $items): void
    {
        $this->items = $items;
    }
}
