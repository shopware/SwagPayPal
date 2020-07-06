<?php declare(strict_types=1);
/*
 * (c) shopware AG <info@shopware.com>
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Swag\PayPal\PayPal\ApiV1\Api\Payment;

use Swag\PayPal\PayPal\ApiV1\Api\Payment\Transaction\Amount;
use Swag\PayPal\PayPal\ApiV1\Api\Payment\Transaction\ItemList;
use Swag\PayPal\PayPal\ApiV1\Api\Payment\Transaction\Payee;
use Swag\PayPal\PayPal\ApiV1\Api\Payment\Transaction\RelatedResource;
use Swag\PayPal\PayPal\PayPalApiStruct;

class Transaction extends PayPalApiStruct
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
     * @var string|null
     */
    protected $invoiceNumber;

    /**
     * @var string
     */
    protected $softDescriptor;

    /**
     * @var string
     */
    protected $description;

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
}
