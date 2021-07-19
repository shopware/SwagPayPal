<?php declare(strict_types=1);
/*
 * (c) shopware AG <info@shopware.com>
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Swag\PayPal\RestApi\V1\Api\Disputes\Item\Extensions\BillingDisputeProperties;

use Swag\PayPal\RestApi\PayPalApiStruct;

class PaymentByOtherMeans extends PayPalApiStruct
{
    /**
     * @deprecated tag:v4.0.0 - will be strongly typed
     *
     * @var bool
     */
    protected $chargeDifferentFromOriginal;

    /**
     * @deprecated tag:v4.0.0 - will be strongly typed
     *
     * @var bool
     */
    protected $receivedDuplicate;

    /**
     * @deprecated tag:v4.0.0 - will be strongly typed
     *
     * @var string
     */
    protected $paymentMethod;

    /**
     * @deprecated tag:v4.0.0 - will be strongly typed
     *
     * @var string
     */
    protected $paymentInstrumentSuffix;

    public function isChargeDifferentFromOriginal(): bool
    {
        return $this->chargeDifferentFromOriginal;
    }

    public function setChargeDifferentFromOriginal(bool $chargeDifferentFromOriginal): void
    {
        $this->chargeDifferentFromOriginal = $chargeDifferentFromOriginal;
    }

    public function isReceivedDuplicate(): bool
    {
        return $this->receivedDuplicate;
    }

    public function setReceivedDuplicate(bool $receivedDuplicate): void
    {
        $this->receivedDuplicate = $receivedDuplicate;
    }

    public function getPaymentMethod(): string
    {
        return $this->paymentMethod;
    }

    public function setPaymentMethod(string $paymentMethod): void
    {
        $this->paymentMethod = $paymentMethod;
    }

    public function getPaymentInstrumentSuffix(): string
    {
        return $this->paymentInstrumentSuffix;
    }

    public function setPaymentInstrumentSuffix(string $paymentInstrumentSuffix): void
    {
        $this->paymentInstrumentSuffix = $paymentInstrumentSuffix;
    }
}
