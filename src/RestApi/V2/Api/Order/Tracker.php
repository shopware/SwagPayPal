<?php declare(strict_types=1);
/*
 * (c) shopware AG <info@shopware.com>
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Swag\PayPal\RestApi\V2\Api\Order;

use OpenApi\Attributes as OA;
use Shopware\Core\Framework\Log\Package;
use Swag\PayPal\RestApi\PayPalApiStruct;
use Swag\PayPal\RestApi\V2\Api\Order\PurchaseUnit\Shipping\Tracker\Item;
use Swag\PayPal\RestApi\V2\Api\Order\PurchaseUnit\Shipping\Tracker\ItemCollection;

#[OA\Schema(schema: 'swag_paypal_v2_order_tracker')]
#[Package('checkout')]
class Tracker extends PayPalApiStruct
{
    public const MAX_LENGTH_TRACKING_NUMBER = 64;
    public const CARRIER_OTHER = 'OTHER';

    #[OA\Property(type: 'string')]
    protected string $captureId;

    #[OA\Property(type: 'string')]
    protected string $trackingNumber;

    #[OA\Property(type: 'string')]
    protected string $carrier;

    #[OA\Property(type: 'string', nullable: true)]
    protected ?string $carrierNameOther = null;

    #[OA\Property(type: 'bool', default: false)]
    protected bool $notifyPayer = false;

    #[OA\Property(type: 'array', items: new OA\Items(ref: Item::class))]
    protected ItemCollection $items;

    public function getCaptureId(): string
    {
        return $this->captureId;
    }

    public function setCaptureId(string $captureId): void
    {
        $this->captureId = $captureId;
    }

    public function getTrackingNumber(): string
    {
        return $this->trackingNumber;
    }

    /**
     * @throws \LengthException if given parameter is too long
     */
    public function setTrackingNumber(string $trackingNumber): void
    {
        if (\mb_strlen($trackingNumber) > self::MAX_LENGTH_TRACKING_NUMBER) {
            throw new \LengthException(
                \sprintf(
                    '%s::$trackingNumber must not be longer than %s characters',
                    self::class,
                    self::MAX_LENGTH_TRACKING_NUMBER
                )
            );
        }

        $this->trackingNumber = $trackingNumber;
    }

    public function getCarrier(): string
    {
        return $this->carrier;
    }

    public function setCarrier(string $carrier): void
    {
        $this->carrier = $carrier;
    }

    public function getCarrierNameOther(): ?string
    {
        return $this->carrierNameOther;
    }

    public function setCarrierNameOther(?string $carrierNameOther): void
    {
        $this->carrierNameOther = $carrierNameOther;
    }

    public function isNotifyPayer(): bool
    {
        return $this->notifyPayer;
    }

    public function setNotifyPayer(bool $notifyPayer): void
    {
        $this->notifyPayer = $notifyPayer;
    }

    public function getItems(): ItemCollection
    {
        return $this->items;
    }

    public function setItems(ItemCollection $items): void
    {
        $this->items = $items;
    }
}
