<?php declare(strict_types=1);
/*
 * (c) shopware AG <info@shopware.com>
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Swag\PayPal\RestApi\V1\Api\Payment;

use OpenApi\Attributes as OA;
use Shopware\Core\Framework\Log\Package;
use Swag\PayPal\RestApi\PayPalApiStruct;
use Swag\PayPal\RestApi\V1\Api\Common\Amount;
use Swag\PayPal\RestApi\V1\Api\Payment\Transaction\ItemList;
use Swag\PayPal\RestApi\V1\Api\Payment\Transaction\Payee;
use Swag\PayPal\RestApi\V1\Api\Payment\Transaction\RelatedResource;
use Swag\PayPal\RestApi\V1\Api\Payment\Transaction\RelatedResourceCollection;

#[OA\Schema(schema: 'swag_paypal_v1_payment_transaction')]
#[Package('checkout')]
class Transaction extends PayPalApiStruct
{
    #[OA\Property(ref: Amount::class)]
    protected Amount $amount;

    #[OA\Property(ref: Payee::class)]
    protected Payee $payee;

    #[OA\Property(ref: ItemList::class, nullable: true)]
    protected ?ItemList $itemList = null;

    #[OA\Property(type: 'array', items: new OA\Items(ref: RelatedResource::class))]
    protected RelatedResourceCollection $relatedResources;

    #[OA\Property(type: 'string', nullable: true)]
    protected ?string $invoiceNumber = null;

    #[OA\Property(type: 'string')]
    protected string $softDescriptor;

    #[OA\Property(type: 'string')]
    protected string $description;

    #[OA\Property(type: 'string')]
    protected string $custom;

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

    public function getRelatedResources(): RelatedResourceCollection
    {
        return $this->relatedResources;
    }

    public function setRelatedResources(RelatedResourceCollection $relatedResources): void
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
