<?php declare(strict_types=1);
/*
 * (c) shopware AG <info@shopware.com>
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Swag\PayPal\Storefront\Data\Struct;

use Shopware\Core\Framework\Struct\Struct;

class AbstractCheckoutData extends Struct
{
    protected string $clientId;

    protected string $merchantPayerId;

    protected string $languageIso;

    protected string $currency;

    protected string $intent;

    protected string $buttonShape;

    protected string $clientToken;

    protected string $paymentMethodId;

    protected string $createOrderUrl;

    protected string $checkoutConfirmUrl;

    protected string $addErrorUrl;

    protected bool $preventErrorReload;

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

    public function getMerchantPayerId(): string
    {
        return $this->merchantPayerId;
    }

    public function setMerchantPayerId(string $merchantPayerId): void
    {
        $this->merchantPayerId = $merchantPayerId;
    }

    public function getLanguageIso(): string
    {
        return $this->languageIso;
    }

    public function setLanguageIso(string $languageIso): void
    {
        $this->languageIso = $languageIso;
    }

    public function getCurrency(): string
    {
        return $this->currency;
    }

    public function setCurrency(string $currency): void
    {
        $this->currency = $currency;
    }

    public function getIntent(): string
    {
        return $this->intent;
    }

    public function setIntent(string $intent): void
    {
        $this->intent = $intent;
    }

    public function getClientToken(): string
    {
        return $this->clientToken;
    }

    public function setClientToken(string $clientToken): void
    {
        $this->clientToken = $clientToken;
    }

    public function getPaymentMethodId(): string
    {
        return $this->paymentMethodId;
    }

    public function setPaymentMethodId(string $paymentMethodId): void
    {
        $this->paymentMethodId = $paymentMethodId;
    }

    public function getCreateOrderUrl(): string
    {
        return $this->createOrderUrl;
    }

    public function setCreateOrderUrl(string $createOrderUrl): void
    {
        $this->createOrderUrl = $createOrderUrl;
    }

    public function getCheckoutConfirmUrl(): string
    {
        return $this->checkoutConfirmUrl;
    }

    public function setCheckoutConfirmUrl(string $checkoutConfirmUrl): void
    {
        $this->checkoutConfirmUrl = $checkoutConfirmUrl;
    }

    public function getAddErrorUrl(): string
    {
        return $this->addErrorUrl;
    }

    public function setAddErrorUrl(string $addErrorUrl): void
    {
        $this->addErrorUrl = $addErrorUrl;
    }

    public function getPreventErrorReload(): bool
    {
        return $this->preventErrorReload;
    }

    public function setPreventErrorReload(bool $preventErrorReload): void
    {
        $this->preventErrorReload = $preventErrorReload;
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

    public function getButtonShape(): string
    {
        return $this->buttonShape;
    }

    public function setButtonShape(string $buttonShape): void
    {
        $this->buttonShape = $buttonShape;
    }
}
