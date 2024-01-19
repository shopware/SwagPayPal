<?php

declare(strict_types=1);
/*
 * (c) shopware AG <info@shopware.com>
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Swag\PayPal\RestApi\V1\Api\Disputes\Item\Evidence;

use OpenApi\Annotations as OA;
use Shopware\Core\Framework\Log\Package;
use Swag\PayPal\RestApi\PayPalApiStruct;
use Swag\PayPal\RestApi\V1\Api\Disputes\Item\Evidence\EvidenceInfo\RefundIdCollection;
use Swag\PayPal\RestApi\V1\Api\Disputes\Item\Evidence\EvidenceInfo\TrackingInfoCollection;

/**
 * @OA\Schema(schema="swag_paypal_v1_disputes_evidence_info")
 */
#[Package('checkout')]
class EvidenceInfo extends PayPalApiStruct
{
    /**
     * @OA\Property(type="array", items={"$ref": "#/components/schemas/swag_paypal_v1_disputes_evidence_tracking_info"})
     */
    protected TrackingInfoCollection $trackingInfo;

    /**
     * @OA\Property(type="array", items={"$ref": "#/components/schemas/swag_paypal_v1_disputes_evidence_refund_id"})
     */
    protected RefundIdCollection $refundIds;

    public function getTrackingInfo(): TrackingInfoCollection
    {
        return $this->trackingInfo;
    }

    public function setTrackingInfo(TrackingInfoCollection $trackingInfo): void
    {
        $this->trackingInfo = $trackingInfo;
    }

    public function getRefundIds(): RefundIdCollection
    {
        return $this->refundIds;
    }

    public function setRefundIds(RefundIdCollection $refundIds): void
    {
        $this->refundIds = $refundIds;
    }
}
