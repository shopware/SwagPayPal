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
     * @var bool
     */
    protected $chargeDifferentFromOriginal;

    /**
     * @var bool
     */
    protected $receivedDuplicate;

    /**
     * @var string
     */
    protected $paymentMethod;

    /**
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
