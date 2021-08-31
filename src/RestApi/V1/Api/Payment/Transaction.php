<?php declare(strict_types=1);
/*
 * (c) shopware AG <info@shopware.com>
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Swag\PayPal\RestApi\V1\Api\Payment;

use OpenApi\Annotations as OA;
use Swag\PayPal\RestApi\PayPalApiStruct;
use Swag\PayPal\RestApi\V1\Api\Payment\Transaction\Amount;
use Swag\PayPal\RestApi\V1\Api\Payment\Transaction\ItemList;
use Swag\PayPal\RestApi\V1\Api\Payment\Transaction\Payee;
use Swag\PayPal\RestApi\V1\Api\Payment\Transaction\RelatedResource;

/**
 * @OA\Schema(schema="swag_paypal_v1_payment_transaction")
 */
class Transaction extends PayPalApiStruct
{
    /**
     * @deprecated tag:v4.0.0 - will be strongly typed
     *
     * @var Amount
     * @OA\Property(ref="#/components/schemas/swag_paypal_v1_common_amount")
     */
    protected $amount;

    /**
     * @deprecated tag:v4.0.0 - will be strongly typed
     *
     * @var Payee
     * @OA\Property(ref="#/components/schemas/swag_paypal_v1_payment_transaction_payee")
     */
    protected $payee;

    /**
     * @deprecated tag:v4.0.0 - will be strongly typed
     *
     * @var ItemList|null
     * @OA\Property(ref="#/components/schemas/swag_paypal_v1_payment_transaction_item_list", nullable=true)
     */
    protected $itemList;

    /**
     * @deprecated tag:v4.0.0 - will be strongly typed
     *
     * @var RelatedResource[]
     * @OA\Property(type="array", items={"$ref": "#/components/schemas/swag_paypal_v1_payment_transaction_related_resource"})
     */
    protected $relatedResources;

    /**
     * @deprecated tag:v4.0.0 - will be strongly typed
     *
     * @var string|null
     * @OA\Property(type="string", nullable=true)
     */
    protected $invoiceNumber;

    /**
     * @deprecated tag:v4.0.0 - will be strongly typed
     *
     * @var string
     * @OA\Property(type="string")
     */
    protected $softDescriptor;

    /**
     * @deprecated tag:v4.0.0 - will be strongly typed
     *
     * @var string
     * @OA\Property(type="string")
     */
    protected $description;

    /**
     * @deprecated tag:v4.0.0 - will be strongly typed
     *
     * @var string
     * @OA\Property(type="string")
     */
    protected $custom;

    public function getAmount(): Amount
    {
        return $this->amount;
    }

    public function setAmount(Amount $amount): void
    {
        $this->amount = $amount;
    }

    public function getPayee(): Payee
    {
        return $this->payee;
    }

    public function setPayee(Payee $payee): void
    {
        $this->payee = $payee;
    }

    public function getItemList(): ?ItemList
    {
        return $this->itemList;
    }

    public function setItemList(?ItemList $itemList): void
    {
        $this->itemList = $itemList;
    }

    /**
     * @return RelatedResource[]
     */
    public function getRelatedResources(): array
    {
        return $this->relatedResources;
    }

    /**
     * @param RelatedResource[] $relatedResources
     */
    public function setRelatedResources(array $relatedResources): void
    {
        $this->relatedResources = $relatedResources;
    }

    public function getInvoiceNumber(): ?string
    {
        return $this->invoiceNumber;
    }

    public function setInvoiceNumber(?string $invoiceNumber): void
    {
        $this->invoiceNumber = $invoiceNumber;
    }

    public function getSoftDescriptor(): string
    {
        return $this->softDescriptor;
    }

    public function setSoftDescriptor(string $softDescriptor): void
    {
        $this->softDescriptor = $softDescriptor;
    }

    public function getDescription(): string
    {
        return $this->description;
    }

    public function setDescription(string $description): void
    {
        $this->description = $description;
    }

    public function getCustom(): string
    {
        return $this->custom;
    }

    public function setCustom(string $custom): void
    {
        $this->custom = $custom;
    }
}
