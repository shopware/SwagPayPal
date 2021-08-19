<?php declare(strict_types=1);
/*
 * (c) shopware AG <info@shopware.com>
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Swag\PayPal\RestApi\V1\Api\Plan;

use OpenApi\Annotations as OA;
use Swag\PayPal\RestApi\PayPalApiStruct;

/**
 * @OA\Schema(schema="swag_paypal_v1_plan_payment_preferences")
 * @codeCoverageIgnore
 * @experimental
 *
 * This class is experimental and not officially supported.
 * It is currently not used within the plugin itself. Use with caution.
 */
class PaymentPreferences extends PayPalApiStruct
{
    /**
     * @deprecated tag:v4.0.0 - will be strongly typed
     *
     * @var bool
     * @OA\Property(type="boolean")
     */
    protected $autoBillOutstanding;

    /**
     * @deprecated tag:v4.0.0 - will be strongly typed
     *
     * @var int
     * @OA\Property(type="integer")
     */
    protected $paymentFailureThreshold;

    public function getAutoBillOutstanding(): bool
    {
        return $this->autoBillOutstanding;
    }

    public function setAutoBillOutstanding(bool $autoBillOutstanding): void
    {
        $this->autoBillOutstanding = $autoBillOutstanding;
    }

    public function getPaymentFailureThreshold(): int
    {
        return $this->paymentFailureThreshold;
    }

    public function setPaymentFailureThreshold(int $paymentFailureThreshold): void
    {
        $this->paymentFailureThreshold = $paymentFailureThreshold;
    }
}
