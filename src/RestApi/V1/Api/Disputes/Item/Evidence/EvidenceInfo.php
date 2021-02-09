<?php declare(strict_types=1);
/*
 * (c) shopware AG <info@shopware.com>
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Swag\PayPal\RestApi\V1\Api\Disputes\Item\Evidence;

use Swag\PayPal\RestApi\PayPalApiStruct;
use Swag\PayPal\RestApi\V1\Api\Disputes\Item\Evidence\EvidenceInfo\RefundId;
use Swag\PayPal\RestApi\V1\Api\Disputes\Item\Evidence\EvidenceInfo\TrackingInfo;

class EvidenceInfo extends PayPalApiStruct
{
    /**
     * @var TrackingInfo[]
     */
    protected $trackingInfo;

    /**
     * @var RefundId[]
     */
    protected $refundIds;

    /**
     * @return TrackingInfo[]
     */
    public function getTrackingInfo(): array
    {
        return $this->trackingInfo;
    }

    /**
     * @param TrackingInfo[] $trackingInfo
     */
    public function setTrackingInfo(array $trackingInfo): void
    {
        $this->trackingInfo = $trackingInfo;
    }

    /**
     * @return RefundId[]
     */
    public function getRefundIds(): array
    {
        return $this->refundIds;
    }

    /**
     * @param RefundId[] $refundIds
     */
    public function setRefundIds(array $refundIds): void
    {
        $this->refundIds = $refundIds;
    }
}
