<?php declare(strict_types=1);
/**
 * (c) shopware AG <info@shopware.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace SwagPayPal\PayPal\Api\Payment;

use SwagPayPal\PayPal\Api\Payment\Transaction\Amount;
use SwagPayPal\PayPal\Api\Payment\Transaction\ItemList;
use SwagPayPal\PayPal\Api\Payment\Transaction\Payee;
use SwagPayPal\PayPal\Api\Payment\Transaction\RelatedResource;
use SwagPayPal\PayPal\Api\PayPalStruct;

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
     * @var ItemList
     */
    protected $itemList;

    /**
     * @var RelatedResource[]
     */
    protected $relatedResources;

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

    protected function setPayee(Payee $payee): void
    {
        $this->payee = $payee;
    }

    protected function setItemList(ItemList $itemList): void
    {
        $this->itemList = $itemList;
    }

    /**
     * @param RelatedResource[] $relatedResources
     */
    protected function setRelatedResources(array $relatedResources): void
    {
        $this->relatedResources = $relatedResources;
    }
}
