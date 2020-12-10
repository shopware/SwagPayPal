<?php declare(strict_types=1);
/*
 * (c) shopware AG <info@shopware.com>
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Swag\PayPal\RestApi\V1\Api\Disputes\Item\Evidence\EvidenceInfo;

use Swag\PayPal\RestApi\PayPalApiStruct;

class RefundId extends PayPalApiStruct
{
    /**
     * @var string
     */
    protected $refundId;

    public function getRefundId(): string
    {
        return $this->refundId;
    }

    public function setRefundId(string $refundId): void
    {
        $this->refundId = $refundId;
    }
}
