<?php declare(strict_types=1);
/**
 * (c) shopware AG <info@shopware.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace SwagPayPal\PayPal\Struct\Payment;

use SwagPayPal\PayPal\Struct\Payment\Transactions\Amount;
use SwagPayPal\PayPal\Struct\Payment\Transactions\ItemList;
use SwagPayPal\PayPal\Struct\Payment\Transactions\RelatedResources;
use SwagPayPal\PayPal\Struct\Payment\Transactions\ShipmentDetails;

class Transactions
{
    /**
     * @var Amount
     */
    private $amount;

    /**
     * @var ItemList
     */
    private $itemList;

    /**
     * @var RelatedResources
     */
    private $relatedResources;

    /**
     * @var ShipmentDetails
     */
    private $shipmentDetails;

    public function getAmount(): Amount
    {
        return $this->amount;
    }

    public function setAmount(Amount $amount): void
    {
        $this->amount = $amount;
    }

    public function getItemList(): ?ItemList
    {
        return $this->itemList;
    }

    public function setItemList(ItemList $itemList): void
    {
        $this->itemList = $itemList;
    }

    public function getRelatedResources(): RelatedResources
    {
        return $this->relatedResources;
    }

    public function setRelatedResources(RelatedResources $relatedResources): void
    {
        $this->relatedResources = $relatedResources;
    }

    public function getShipmentDetails(): ?ShipmentDetails
    {
        return $this->shipmentDetails;
    }

    public function setShipmentDetails(ShipmentDetails $shipmentDetails): void
    {
        $this->shipmentDetails = $shipmentDetails;
    }

    public static function fromArray(array $data = []): Transactions
    {
        $result = new self();

        if (array_key_exists('amount', $data)) {
            $result->setAmount(Amount::fromArray($data['amount']));

            if (array_key_exists('item_list', $data)) {
                $result->setItemList(ItemList::fromArray($data['item_list']));
            }
        }

        if (array_key_exists('related_resources', $data)) {
            $result->setRelatedResources(RelatedResources::fromArray($data['related_resources']));
        }

        if (array_key_exists('shipment_details', $data)) {
            $result->setShipmentDetails(ShipmentDetails::fromArray($data['shipment_details']));
        }

        return $result;
    }

    public function toArray(): array
    {
        $result = [
            'amount' => $this->getAmount()->toArray(),
        ];

        if ($this->getShipmentDetails() !== null) {
            $result['shipment_details'] = $this->getShipmentDetails()->toArray();
        }

        if ($this->getItemList() === null) {
            return $result;
        }

        $result['item_list'] = $this->getItemList()->toArray();

        return $result;
    }
}
