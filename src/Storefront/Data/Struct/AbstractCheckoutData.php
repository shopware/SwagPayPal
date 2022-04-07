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

    /**
     * @deprecated tag:v6.0.0 - will be nullable
     */
    protected string $clientToken;

    protected string $paymentMethodId;

    protected string $createOrderUrl;

    /**
     * @deprecated tag:v6.0.0 - will be removed
     */
    protected string $checkoutConfirmUrl;

    protected string $addErrorUrl;

    protected bool $preventErrorReload;

    protected ?string $orderId = null;

    /**
     * @deprecated tag:v6.0.0 - will be removed
     */
    protected ?string $accountOrderEditUrl = null;

    protected ?string $accountOrderEditCancelledUrl = null;

    protected ?string $accountOrderEditFailedUrl = null;

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

    /**
     * @deprecated tag:v6.0.0 - return type will be nullable
     */
    public function getClientToken(): string
    {
        return $this->clientToken;
    }

    /**
     * @deprecated tag:v6.0.0 - param $clientToken will be nullable
     */
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

    /**
     * @deprecated tag:v6.0.0 - will be removed
     */
    public function getCheckoutConfirmUrl(): string
    {
        return $this->checkoutConfirmUrl;
    }

    /**
     * @deprecated tag:v6.0.0 - will be removed
     */
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

    /**
     * @deprecated tag:v6.0.0 - will be removed
     */
    public function getAccountOrderEditUrl(): ?string
    {
        return $this->accountOrderEditUrl;
    }

    /**
     * @deprecated tag:v6.0.0 - will be removed
     */
    public function setAccountOrderEditUrl(?string $accountOrderEditUrl): void
    {
        $this->accountOrderEditUrl = $accountOrderEditUrl;
    }

    public function getAccountOrderEditCancelledUrl(): ?string
    {
        return $this->accountOrderEditCancelledUrl;
    }

    public function setAccountOrderEditCancelledUrl(?string $accountOrderEditCancelledUrl): void
    {
        $this->accountOrderEditCancelledUrl = $accountOrderEditCancelledUrl;
    }

    public function getAccountOrderEditFailedUrl(): ?string
    {
        return $this->accountOrderEditFailedUrl;
    }

    public function setAccountOrderEditFailedUrl(?string $accountOrderEditFailedUrl): void
    {
        $this->accountOrderEditFailedUrl = $accountOrderEditFailedUrl;
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
