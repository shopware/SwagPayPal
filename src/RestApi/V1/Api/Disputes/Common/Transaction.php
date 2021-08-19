<?php declare(strict_types=1);
/*
 * (c) shopware AG <info@shopware.com>
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Swag\PayPal\RestApi\V1\Api\Disputes\Common;

use OpenApi\Annotations as OA;
use Swag\PayPal\RestApi\PayPalApiStruct;
use Swag\PayPal\RestApi\V1\Api\Common\Money;

/**
 * @OA\Schema(schema="swag_paypal_v1_disputes_common_transaction")
 */
abstract class Transaction extends PayPalApiStruct
{
    /**
     * @deprecated tag:v4.0.0 - will be strongly typed
     *
     * @var string
     * @OA\Property(type="string")
     */
    protected $buyerTransactionId;

    /**
     * @deprecated tag:v4.0.0 - will be strongly typed
     *
     * @var string
     * @OA\Property(type="string")
     */
    protected $sellerTransactionId;

    /**
     * @deprecated tag:v4.0.0 - will be strongly typed
     *
     * @var string
     * @OA\Property(type="string")
     */
    protected $referenceId;

    /**
     * @deprecated tag:v4.0.0 - will be strongly typed
     *
     * @var string
     * @OA\Property(type="string")*
     */
    protected $createTime;

    /**
     * @deprecated tag:v4.0.0 - will be strongly typed
     *
     * @var string
     * @OA\Property(type="string")
     */
    protected $transactionStatus;

    /**
     * @deprecated tag:v4.0.0 - will be strongly typed
     *
     * @var Money
     * @OA\Property(ref="#/components/schemas/swag_paypal_v1_common_money")
     */
    protected $grossAmount;

    /**
     * @deprecated tag:v4.0.0 - will be strongly typed
     *
     * @var string
     * @OA\Property(type="string")
     */
    protected $invoiceNumber;

    /**
     * @deprecated tag:v4.0.0 - will be strongly typed
     *
     * @var string
     * @OA\Property(type="string")
     */
    protected $custom;

    /**
     * @deprecated tag:v4.0.0 - will be strongly typed
     *
     * @var Buyer
     * @OA\Property(ref="#/components/schemas/swag_paypal_v1_disputes_common_buyer")
     */
    protected $buyer;

    /**
     * @deprecated tag:v4.0.0 - will be strongly typed
     *
     * @var Seller
     * @OA\Property(ref="#/components/schemas/swag_paypal_v1_disputes_common_seller")
     */
    protected $seller;

    /**
     * @deprecated tag:v4.0.0 - will be strongly typed
     *
     * @var Item[]
     * @OA\Property(type="array", items={"$ref": "#/components/schemas/swag_paypal_v1_disputes_common_item"})
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
