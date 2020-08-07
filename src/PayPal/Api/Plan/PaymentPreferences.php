<?php
declare(strict_types=1);
/*
 * (c) shopware AG <info@shopware.com>
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Swag\PayPal\PayPal\Api\Plan;

use Swag\PayPal\PayPal\Api\Common\PayPalStruct;

class PaymentPreferences extends PayPalStruct
{
    /** @var bool */
    protected $autoBillOutstanding;

    /** @var int */
    protected $paymentFailureThreshold;

    public function isAutoBillOutstanding(): bool
    {
        return $this->autoBillOutstanding;
    }

    public function setAutoBillOutstanding(bool $autoBillOutstanding): self
    {
        $this->autoBillOutstanding = $autoBillOutstanding;

        return $this;
    }

    public function getPaymentFailureThreshold(): int
    {
        return $this->paymentFailureThreshold;
    }

    public function setPaymentFailureThreshold(int $paymentFailureThreshold): self
    {
        $this->paymentFailureThreshold = $paymentFailureThreshold;

        return $this;
    }
}
