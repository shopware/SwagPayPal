<?php declare(strict_types=1);
/*
 * (c) shopware AG <info@shopware.com>
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Swag\PayPal\RestApi\V1\Api\Disputes\Item;

use OpenApi\Annotations as OA;
use Swag\PayPal\RestApi\PayPalApiStruct;
use Swag\PayPal\RestApi\V1\Api\Disputes\Item\Offer\BuyerRequestedAmount;
use Swag\PayPal\RestApi\V1\Api\Disputes\Item\Offer\History;
use Swag\PayPal\RestApi\V1\Api\Disputes\Item\Offer\SellerOfferedAmount;

/**
 * @OA\Schema(schema="swag_paypal_v1_disputes_offer")
 */
class Offer extends PayPalApiStruct
{
    /**
     * @OA\Property(ref="#/components/schemas/swag_paypal_v1_common_money")
     */
    protected BuyerRequestedAmount $buyerRequestedAmount;

    /**
     * @OA\Property(ref="#/components/schemas/swag_paypal_v1_common_money")
     */
    protected SellerOfferedAmount $sellerOfferedAmount;

    /**
     * @OA\Property(type="string")
     */
    protected string $offerType;

    /**
     * @var History[]|null
     *
     * @OA\Property(type="array", items={"$ref": "#/components/schemas/swag_paypal_v1_disputes_offer_history"}, nullable=true)
     */
    protected ?array $history = null;

    public function getBuyerRequestedAmount(): BuyerRequestedAmount
    {
        return $this->buyerRequestedAmount;
    }

    public function setBuyerRequestedAmount(BuyerRequestedAmount $buyerRequestedAmount): void
    {
        $this->buyerRequestedAmount = $buyerRequestedAmount;
    }

    public function getSellerOfferedAmount(): SellerOfferedAmount
    {
        return $this->sellerOfferedAmount;
    }

    public function setSellerOfferedAmount(SellerOfferedAmount $sellerOfferedAmount): void
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

    /**
     * @return History[]|null
     */
    public function getHistory(): ?array
    {
        return $this->history;
    }

    /**
     * @param History[]|null $history
     */
    public function setHistory(?array $history): void
    {
        $this->history = $history;
    }
}
