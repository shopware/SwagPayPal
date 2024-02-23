<?php declare(strict_types=1);
/*
 * (c) shopware AG <info@shopware.com>
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Swag\PayPal\RestApi\V1\Api\Shipping;

use OpenApi\Attributes as OA;
use Shopware\Core\Framework\Log\Package;
use Swag\PayPal\RestApi\PayPalApiStruct;

#[OA\Schema(schema: 'swag_paypal_v1_shipping_tracker')]
#[Package('checkout')]
class Tracker extends PayPalApiStruct
{
    public const STATUS_SHIPPED = 'SHIPPED';
    public const STATUS_CANCELLED = 'CANCELLED';

    #[OA\Property(type: 'string')]
    protected string $transactionId;

    #[OA\Property(type: 'string')]
    protected string $trackingNumber;

    #[OA\Property(type: 'string')]
    protected string $status;

    #[OA\Property(type: 'string')]
    protected string $carrier;

    #[OA\Property(type: 'boolean')]
    protected bool $notifyBuyer;

    /**
     * Pattern: '2022-08-15'
     */
    #[OA\Property(type: 'string')]
    protected string $shipmentDate;

    public function getTransactionId(): string
    {
        return $this->transactionId;
    }

    public function setTransactionId(string $transactionId): void
    {
        $this->transactionId = $transactionId;
    }

    public function getTrackingNumber(): string
    {
        return $this->trackingNumber;
    }

    public function setTrackingNumber(string $trackingNumber): void
    {
        $this->trackingNumber = $trackingNumber;
    }

    public function getStatus(): string
    {
        return $this->status;
    }

    public function setStatus(string $status): void
    {
        $this->status = $status;
    }

    public function getCarrier(): string
    {
        return $this->carrier;
    }

    public function setCarrier(string $carrier): void
    {
        $this->carrier = $carrier;
    }

    public function isNotifyBuyer(): bool
    {
        return $this->notifyBuyer;
    }

    public function setNotifyBuyer(bool $notifyBuyer): void
    {
        $this->notifyBuyer = $notifyBuyer;
    }

    public function getShipmentDate(): string
    {
        return $this->shipmentDate;
    }

    public function setShipmentDate(string $shipmentDate): void
    {
        $this->shipmentDate = $shipmentDate;
    }
}
