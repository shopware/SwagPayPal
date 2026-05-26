<?php declare(strict_types=1);
/*
 * (c) shopware AG <info@shopware.com>
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Swag\PayPal\AgenticCommerce\Struct\V1\Context;

use OpenApi\Attributes as OA;
use Shopware\Core\Framework\Log\Package;

/**
 * @experimental
 */
#[Package('checkout')]
#[OA\Schema(schema: 'paypal_agentic_commerce_v1_context_payment_error_context')]
class PaymentErrorContext extends AbstractContext
{
    public const ISSUE__PAYMENT_AMOUNT_TOO_LARGE = 'PAYMENT_AMOUNT_TOO_LARGE';
    public const ISSUE__PAYMENT_AMOUNT_TOO_SMALL = 'PAYMENT_AMOUNT_TOO_SMALL';
    public const ISSUE__PAYMENT_METHOD_NOT_ACCEPTED = 'PAYMENT_METHOD_NOT_ACCEPTED';
    public const ISSUE__CURRENCY_CONVERSION_FAILED = 'CURRENCY_CONVERSION_FAILED';
    public const ISSUE__PAYMENT_PROCESSOR_UNAVAILABLE = 'PAYMENT_PROCESSOR_UNAVAILABLE';
    public const ISSUE__MERCHANT_ACCOUNT_ISSUE = 'MERCHANT_ACCOUNT_ISSUE';
    public const ISSUE__PAYMENT_DECLINED = 'PAYMENT_DECLINED';
    public const ISSUE__PAYMENT_INSUFFICIENT_FUNDS = 'PAYMENT_INSUFFICIENT_FUNDS';
    public const ISSUE__PAYMENT_EXPIRED = 'PAYMENT_EXPIRED';
    public const ISSUE__PAYMENT_FRAUD_DETECTED = 'PAYMENT_FRAUD_DETECTED';

    /**
     * Total order amount
     */
    #[OA\Property(type: 'string')]
    protected ?string $orderTotal = null;

    /**
     * Maximum payment limit
     */
    #[OA\Property(type: 'string')]
    protected ?string $paymentLimit = null;

    /**
     * Minimum payment amount
     */
    #[OA\Property(type: 'string')]
    protected ?string $minimumAmount = null;

    /**
     * Amount exceeding limit
     */
    #[OA\Property(type: 'string')]
    protected ?string $excessAmount = null;

    /**
     * Payment method being used
     */
    #[OA\Property(type: 'string')]
    protected ?string $paymentMethod = null;

    /**
     * Transaction currency
     */
    #[OA\Property(type: 'string')]
    protected ?string $currencyCode = null;

    /**
     * Source currency for conversion
     */
    #[OA\Property(type: 'string')]
    protected ?string $fromCurrency = null;

    /**
     * Target currency for conversion
     */
    #[OA\Property(type: 'string')]
    protected ?string $toCurrency = null;

    /**
     * Currency conversion service status
     */
    #[OA\Property(type: 'string')]
    protected ?string $conversionService = null;

    /**
     * List of supported payment methods
     *
     * @var string[]
     */
    #[OA\Property(
        type: 'array',
        items: new OA\Items(type: 'string'),
    )]
    protected ?array $supportedPaymentMethods = null;

    /**
     * Payment processor specific error code
     */
    #[OA\Property(type: 'string')]
    protected ?string $processorErrorCode = null;

    /**
     * Reason for payment decline
     */
    #[OA\Property(type: 'string')]
    protected ?string $declineReason = null;

    /**
     * Payment token that was declined
     */
    #[OA\Property(type: 'string')]
    protected ?string $paymentToken = null;

    public function getOrderTotal(): ?string
    {
        return $this->orderTotal;
    }

    public function setOrderTotal(?string $orderTotal): void
    {
        $this->orderTotal = $orderTotal;
    }

    public function getPaymentLimit(): ?string
    {
        return $this->paymentLimit;
    }

    public function setPaymentLimit(?string $paymentLimit): void
    {
        $this->paymentLimit = $paymentLimit;
    }

    public function getMinimumAmount(): ?string
    {
        return $this->minimumAmount;
    }

    public function setMinimumAmount(?string $minimumAmount): void
    {
        $this->minimumAmount = $minimumAmount;
    }

    public function getExcessAmount(): ?string
    {
        return $this->excessAmount;
    }

    public function setExcessAmount(?string $excessAmount): void
    {
        $this->excessAmount = $excessAmount;
    }

    public function getPaymentMethod(): ?string
    {
        return $this->paymentMethod;
    }

    public function setPaymentMethod(?string $paymentMethod): void
    {
        $this->paymentMethod = $paymentMethod;
    }

    public function getCurrencyCode(): ?string
    {
        return $this->currencyCode;
    }

    public function setCurrencyCode(?string $currencyCode): void
    {
        $this->currencyCode = $currencyCode;
    }

    public function getFromCurrency(): ?string
    {
        return $this->fromCurrency;
    }

    public function setFromCurrency(?string $fromCurrency): void
    {
        $this->fromCurrency = $fromCurrency;
    }

    public function getToCurrency(): ?string
    {
        return $this->toCurrency;
    }

    public function setToCurrency(?string $toCurrency): void
    {
        $this->toCurrency = $toCurrency;
    }

    public function getConversionService(): ?string
    {
        return $this->conversionService;
    }

    public function setConversionService(?string $conversionService): void
    {
        $this->conversionService = $conversionService;
    }

    /**
     * @return ?string[]
     */
    public function getSupportedPaymentMethods(): ?array
    {
        return $this->supportedPaymentMethods;
    }

    /**
     * @param ?string[] $supportedPaymentMethods
     */
    public function setSupportedPaymentMethods(?array $supportedPaymentMethods): void
    {
        $this->supportedPaymentMethods = $supportedPaymentMethods;
    }

    public function addSupportedPaymentMethod(string $supportedPaymentMethod): void
    {
        $this->supportedPaymentMethods[] = $supportedPaymentMethod;
    }

    public function getProcessorErrorCode(): ?string
    {
        return $this->processorErrorCode;
    }

    public function setProcessorErrorCode(?string $processorErrorCode): void
    {
        $this->processorErrorCode = $processorErrorCode;
    }

    public function getDeclineReason(): ?string
    {
        return $this->declineReason;
    }

    public function setDeclineReason(?string $declineReason): void
    {
        $this->declineReason = $declineReason;
    }

    public function getPaymentToken(): ?string
    {
        return $this->paymentToken;
    }

    public function setPaymentToken(?string $paymentToken): void
    {
        $this->paymentToken = $paymentToken;
    }

    protected static function getSpecificIssues(): array
    {
        return [
            self::ISSUE__PAYMENT_AMOUNT_TOO_LARGE,
            self::ISSUE__PAYMENT_AMOUNT_TOO_SMALL,
            self::ISSUE__PAYMENT_METHOD_NOT_ACCEPTED,
            self::ISSUE__CURRENCY_CONVERSION_FAILED,
            self::ISSUE__PAYMENT_PROCESSOR_UNAVAILABLE,
            self::ISSUE__MERCHANT_ACCOUNT_ISSUE,
            self::ISSUE__PAYMENT_DECLINED,
            self::ISSUE__PAYMENT_INSUFFICIENT_FUNDS,
            self::ISSUE__PAYMENT_EXPIRED,
            self::ISSUE__PAYMENT_FRAUD_DETECTED,
        ];
    }
}
