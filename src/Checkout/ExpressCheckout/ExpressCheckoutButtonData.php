<?php declare(strict_types=1);
/*
 * (c) shopware AG <info@shopware.com>
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Swag\PayPal\Checkout\ExpressCheckout;

use Shopware\Core\Framework\Struct\Struct;

class ExpressCheckoutButtonData extends Struct
{
    protected bool $productDetailEnabled;

    protected bool $offCanvasEnabled;

    protected bool $loginEnabled;

    protected bool $listingEnabled;

    protected string $buttonColor;

    protected string $buttonShape;

    protected string $languageIso;

    protected bool $cartEnabled;

    protected string $clientId;

    protected string $currency;

    protected string $intent;

    protected bool $addProductToCart;

    protected string $contextSwitchUrl;

    protected ?string $payPalPaymentMethodId = null;

    protected string $createOrderUrl;

    protected string $deleteCartUrl;

    protected string $prepareCheckoutUrl;

    protected string $checkoutConfirmUrl;

    protected string $addErrorUrl;

    protected string $cancelRedirectUrl;

    protected bool $disablePayLater;

    public function getProductDetailEnabled(): bool
    {
        return $this->productDetailEnabled;
    }

    public function getOffCanvasEnabled(): bool
    {
        return $this->offCanvasEnabled;
    }

    public function getLoginEnabled(): bool
    {
        return $this->loginEnabled;
    }

    public function getListingEnabled(): bool
    {
        return $this->listingEnabled;
    }

    public function getButtonColor(): string
    {
        return $this->buttonColor;
    }

    public function getButtonShape(): string
    {
        return $this->buttonShape;
    }

    public function getLanguageIso(): string
    {
        return $this->languageIso;
    }

    public function getCartEnabled(): bool
    {
        return $this->cartEnabled;
    }

    public function getClientId(): string
    {
        return $this->clientId;
    }

    public function setClientId(string $clientId): void
    {
        $this->clientId = $clientId;
    }

    public function getCurrency(): string
    {
        return $this->currency;
    }

    public function getIntent(): string
    {
        return $this->intent;
    }

    public function getAddProductToCart(): bool
    {
        return $this->addProductToCart;
    }

    public function getContextSwitchUrl(): string
    {
        return $this->contextSwitchUrl;
    }

    public function getPayPalPaymentMethodId(): ?string
    {
        return $this->payPalPaymentMethodId;
    }

    public function getCreateOrderUrl(): string
    {
        return $this->createOrderUrl;
    }

    public function getDeleteCartUrl(): string
    {
        return $this->deleteCartUrl;
    }

    public function getPrepareCheckoutUrl(): string
    {
        return $this->prepareCheckoutUrl;
    }

    public function getCheckoutConfirmUrl(): string
    {
        return $this->checkoutConfirmUrl;
    }

    public function getAddErrorUrl(): string
    {
        return $this->addErrorUrl;
    }

    public function getCancelRedirectUrl(): string
    {
        return $this->cancelRedirectUrl;
    }

    public function isDisablePayLater(): bool
    {
        return $this->disablePayLater;
    }

    public function setDisablePayLater(bool $disablePayLater): void
    {
        $this->disablePayLater = $disablePayLater;
    }
}
