<?php declare(strict_types=1);
/*
 * (c) shopware AG <info@shopware.com>
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Swag\PayPal\RestApi\V1\Api\Disputes\Item\Evidence\EvidenceInfo;

use OpenApi\Annotations as OA;
use Swag\PayPal\RestApi\PayPalApiStruct;

/**
 * @OA\Schema(schema="swag_paypal_v1_disputes_evidence_tracking_info")
 */
class TrackingInfo extends PayPalApiStruct
{
    /**
     * @deprecated tag:v4.0.0 - will be strongly typed
     *
     * @var string
     * @OA\Property(type="string")
     */
    protected $carrierName;

    /**
     * @deprecated tag:v4.0.0 - will be strongly typed
     *
     * @var string
     * @OA\Property(type="string")
     */
    protected $carrierNameOther;

    /**
     * @deprecated tag:v4.0.0 - will be strongly typed
     *
     * @var string
     * @OA\Property(type="string")
     */
    protected $trackingUrl;

    /**
     * @deprecated tag:v4.0.0 - will be strongly typed
     *
     * @var string
     * @OA\Property(type="string")
     */
    protected $trackingNumber;

    public function getCarrierName(): string
    {
        return $this->carrierName;
    }

    public function setCarrierName(string $carrierName): void
    {
        $this->carrierName = $carrierName;
    }

    public function getCarrierNameOther(): string
    {
        return $this->carrierNameOther;
    }

    public function setCarrierNameOther(string $carrierNameOther): void
    {
        $this->carrierNameOther = $carrierNameOther;
    }

    public function getTrackingUrl(): string
    {
        return $this->trackingUrl;
    }

    public function setTrackingUrl(string $trackingUrl): void
    {
        $this->trackingUrl = $trackingUrl;
    }

    public function getTrackingNumber(): string
    {
        return $this->trackingNumber;
    }

    public function setTrackingNumber(string $trackingNumber): void
    {
        $this->trackingNumber = $trackingNumber;
    }
}
