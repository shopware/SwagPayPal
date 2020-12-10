<?php declare(strict_types=1);
/*
 * (c) shopware AG <info@shopware.com>
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Swag\PayPal\RestApi\V1\Api\Disputes\Item;

use Swag\PayPal\RestApi\PayPalApiStruct;
use Swag\PayPal\RestApi\V1\Api\Disputes\Item\Offer\BuyerRequestedAmount;
use Swag\PayPal\RestApi\V1\Api\Disputes\Item\Offer\SellerOfferedAmount;

class Offer extends PayPalApiStruct
{
    /**
     * @var BuyerRequestedAmount
     */
    protected $buyerRequestedAmount;

    /**
     * @var SellerOfferedAmount
     */
    protected $sellerOfferedAmount;

    /**
     * @var string
     */
    protected $offerType;

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
}
