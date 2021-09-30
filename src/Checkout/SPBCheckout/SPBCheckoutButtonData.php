<?php declare(strict_types=1);
/*
 * (c) shopware AG <info@shopware.com>
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Swag\PayPal\Checkout\SPBCheckout;

use Shopware\Core\Framework\Struct\Struct;

/**
 * @deprecated tag:v5.0.0 - will be removed, unbranded APMs will be introduced in v4.0.0 as replacement
 */
class SPBCheckoutButtonData extends Struct
{
    protected string $clientId;

    protected string $languageIso;

    protected string $currency;

    protected string $intent;

    protected string $buttonColor;

    protected string $buttonShape;

    protected string $paymentMethodId;

    protected bool $useAlternativePaymentMethods;

    /**
     * @var string[]
     */
    protected array $disabledAlternativePaymentMethods;

    protected string $createOrderUrl;

    protected string $checkoutConfirmUrl;

    protected string $addErrorUrl;

    protected ?string $orderId = null;

    protected ?string $accountOrderEditUrl = null;

    public function getClientId(): string
    {
        return $this->clientId;
    }

    public function setClientId(string $clientId): void
    {
        $this->clientId = $clientId;
    }

    public function getLanguageIso(): string
    {
        return $this->languageIso;
    }

    public function getCurrency(): string
    {
        return $this->currency;
    }

    public function getIntent(): string
    {
        return $this->intent;
    }

    public function getButtonColor(): string
    {
        return $this->buttonColor;
    }

    public function getButtonShape(): string
    {
        return $this->buttonShape;
    }

    public function getPaymentMethodId(): string
    {
        return $this->paymentMethodId;
    }

    public function getUseAlternativePaymentMethods(): bool
    {
        return $this->useAlternativePaymentMethods;
    }

    /**
     * @return string[]
     */
    public function getDisabledAlternativePaymentMethods(): array
    {
        return $this->disabledAlternativePaymentMethods;
    }

    /**
     * @param string[] $disabledAlternativePaymentMethods
     */
    public function setDisabledAlternativePaymentMethods(array $disabledAlternativePaymentMethods): void
    {
        $this->disabledAlternativePaymentMethods = $disabledAlternativePaymentMethods;
    }

    public function getCreateOrderUrl(): string
    {
        return $this->createOrderUrl;
    }

    public function getCheckoutConfirmUrl(): string
    {
        return $this->checkoutConfirmUrl;
    }

    public function getAddErrorUrl(): string
    {
        return $this->addErrorUrl;
    }

    public function getOrderId(): ?string
    {
        return $this->orderId;
    }

    public function setOrderId(?string $orderId): void
    {
        $this->orderId = $orderId;
    }

    public function getAccountOrderEditUrl(): ?string
    {
        return $this->accountOrderEditUrl;
    }

    public function setAccountOrderEditUrl(?string $accountOrderEditUrl): void
    {
        $this->accountOrderEditUrl = $accountOrderEditUrl;
    }
}
