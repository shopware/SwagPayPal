<?php declare(strict_types=1);

namespace Swag\PayPal\PayPal\Api\Payment;

use Swag\PayPal\PayPal\Api\Common\PayPalStruct;
use Swag\PayPal\PayPal\Api\Payment\Transaction\Amount;
use Swag\PayPal\PayPal\Api\Payment\Transaction\ItemList;
use Swag\PayPal\PayPal\Api\Payment\Transaction\Payee;
use Swag\PayPal\PayPal\Api\Payment\Transaction\RelatedResource;

class Transaction extends PayPalStruct
{
    /**
     * @var Amount
     */
    protected $amount;

    /**
     * @var Payee
     */
    protected $payee;

    /**
     * @var ItemList|null
     */
    protected $itemList;

    /**
     * @var RelatedResource[]
     */
    protected $relatedResources;

    /**
     * @var string
     */
    protected $invoiceNumber;

    /**
     * @var string
     */
    private $softDescriptor;

    /**
     * @var string
     */
    private $description;

    /**
     * @return RelatedResource[]
     */
    public function getRelatedResources(): array
    {
        return $this->relatedResources;
    }

    public function getAmount(): Amount
    {
        return $this->amount;
    }

    public function getItemList(): ?ItemList
    {
        return $this->itemList;
    }

    public function setAmount(Amount $amount): void
    {
        $this->amount = $amount;
    }

    public function setItemList(?ItemList $itemList): void
    {
        $this->itemList = $itemList;
    }

    public function setInvoiceNumber(string $invoiceNumber): void
    {
        $this->invoiceNumber = $invoiceNumber;
    }

    protected function setSoftDescriptor(string $softDescriptor): void
    {
        $this->softDescriptor = $softDescriptor;
    }

    protected function setPayee(Payee $payee): void
    {
        $this->payee = $payee;
    }

    protected function setDescription(string $description): void
    {
        $this->description = $description;
    }

    /**
     * @param RelatedResource[] $relatedResources
     */
    protected function setRelatedResources(array $relatedResources): void
    {
        $this->relatedResources = $relatedResources;
    }
}
