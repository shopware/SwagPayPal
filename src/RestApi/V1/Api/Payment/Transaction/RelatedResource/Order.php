<?php declare(strict_types=1);
/*
 * (c) shopware AG <info@shopware.com>
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Swag\PayPal\RestApi\V1\Api\Payment\Transaction\RelatedResource;

use OpenApi\Annotations as OA;

/**
 * @OA\Schema(schema="swag_paypal_v1_payment_transaction_order")
 */
class Order extends RelatedResource
{
    /**
     * @deprecated tag:v4.0.0 - will be strongly typed
     *
     * @var string
     * @OA\Property(type="string")
     */
    protected $reasonCode;

    public function getReasonCode(): string
    {
        return $this->reasonCode;
    }

    public function setReasonCode(string $reasonCode): void
    {
        $this->reasonCode = $reasonCode;
    }
}
