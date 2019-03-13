<?php declare(strict_types=1);
/**
 * (c) shopware AG <info@shopware.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace SwagPayPal\PayPal\Api\Payment;

use SwagPayPal\PayPal\Api\Common\PayPalStruct;
use SwagPayPal\PayPal\Api\Payment\Transaction\Amount;
use SwagPayPal\PayPal\Api\Payment\Transaction\ItemList;
use SwagPayPal\PayPal\Api\Payment\Transaction\Payee;
use SwagPayPal\PayPal\Api\Payment\Transaction\RelatedResource;

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
     * @var string
     */
    protected $description;

    /**
     * @var ItemList
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
     * @return RelatedResource[]
     */
    public function getRelatedResources(): array
    {
        return $this->relatedResources;
    }

    public function setAmount(Amount $amount): void
    {
        $this->amount = $amount;
    }

    public function setItemList(ItemList $itemList): void
    {
        $this->itemList = $itemList;
    }

    protected function setInvoiceNumber(string $invoiceNumber): void
    {
        $this->invoiceNumber = $invoiceNumber;
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
