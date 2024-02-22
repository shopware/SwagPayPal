<?php declare(strict_types=1);
/*
 * (c) shopware AG <info@shopware.com>
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Swag\PayPal\Checkout\Plus;

use Shopware\Core\Framework\Log\Package;
use Shopware\Core\Framework\Struct\Struct;

/**
 * @deprecated tag:v10.0.0 - Will be removed without replacement.
 */
#[Package('checkout')]
class PlusData extends Struct
{
    protected string $approvalUrl;

    protected string $customerCountryIso;

    protected string $mode;

    protected string $customerSelectedLanguage;

    protected string $paymentMethodId;

    protected string $paypalPaymentId;

    protected string $paypalToken;

    protected string $handlePaymentUrl;

    protected string $isEnabledParameterName;

    protected string $languageId;

    protected ?string $orderId = null;

    public function getApprovalUrl(): string
    {
        return $this->approvalUrl;
    }

    public function getCustomerCountryIso(): string
    {
        return $this->customerCountryIso;
    }

    public function getMode(): string
    {
        return $this->mode;
    }

    public function getCustomerSelectedLanguage(): string
    {
        return $this->customerSelectedLanguage;
    }

    public function getPaymentMethodId(): string
    {
        return $this->paymentMethodId;
    }

    public function getPaypalPaymentId(): string
    {
        return $this->paypalPaymentId;
    }

    public function getPaypalToken(): string
    {
        return $this->paypalToken;
    }

    public function getHandlePaymentUrl(): string
    {
        return $this->handlePaymentUrl;
    }

    public function getIsEnabledParameterName(): string
    {
        return $this->isEnabledParameterName;
    }

    public function getLanguageId(): string
    {
        return $this->languageId;
    }

    public function getOrderId(): ?string
    {
        return $this->orderId;
    }

    public function setOrderId(?string $orderId): void
    {
        $this->orderId = $orderId;
    }
}
