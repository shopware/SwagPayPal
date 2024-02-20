<?php declare(strict_types=1);
/*
 * (c) shopware AG <info@shopware.com>
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Swag\PayPal\RestApi\V1\Api\Disputes\Item;

use OpenApi\Attributes as OA;
use Shopware\Core\Framework\Log\Package;
use Swag\PayPal\RestApi\PayPalApiStruct;
use Swag\PayPal\RestApi\V1\Api\Common\Money;
use Swag\PayPal\RestApi\V1\Api\Disputes\Item\Offer\History;
use Swag\PayPal\RestApi\V1\Api\Disputes\Item\Offer\HistoryCollection;

#[OA\Schema(schema: 'swag_paypal_v1_disputes_item_offer')]
#[Package('checkout')]
class Offer extends PayPalApiStruct
{
    #[OA\Property(ref: Money::class)]
    protected Money $buyerRequestedAmount;

    #[OA\Property(ref: Money::class)]
    protected Money $sellerOfferedAmount;

    #[OA\Property(type: 'string')]
    protected string $offerType;

    #[OA\Property(type: 'array', items: new OA\Items(ref: History::class), nullable: true)]
    protected ?HistoryCollection $history = null;

    public function getBuyerRequestedAmount(): Money
    {
        return $this->buyerRequestedAmount;
    }

    public function setBuyerRequestedAmount(Money $buyerRequestedAmount): void
    {
        $this->buyerRequestedAmount = $buyerRequestedAmount;
    }

    public function getSellerOfferedAmount(): Money
    {
        return $this->sellerOfferedAmount;
    }

    public function setSellerOfferedAmount(Money $sellerOfferedAmount): void
    {
        $this->sellerOfferedAmount = $sellerOfferedAmount;
    }

    public function getOfferType(): string
    {
        return $this->offerType;
    }

    public function setOfferType(string $offerType): void
    {
        $this->offerType = $offerType;
    }

    public function getHistory(): ?HistoryCollection
    {
        return $this->history;
    }

    public function setHistory(?HistoryCollection $history): void
    {
        $this->history = $history;
    }
}
