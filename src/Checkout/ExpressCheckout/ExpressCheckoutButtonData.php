<?php declare(strict_types=1);
/*
 * (c) shopware AG <info@shopware.com>
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Swag\PayPal\Checkout\ExpressCheckout;

use Shopware\Core\Framework\Log\Package;
use Swag\PayPal\Storefront\Data\Struct\AbstractScriptData;

#[Package('checkout')]
class ExpressCheckoutButtonData extends AbstractScriptData
{
    protected bool $productDetailEnabled;

    protected bool $offCanvasEnabled;

    protected bool $loginEnabled;

    protected bool $listingEnabled;

    protected bool $cartEnabled;

    protected string $buttonColor;

    protected string $buttonShape;

    protected bool $addProductToCart;

    protected string $contextSwitchUrl;

    protected ?string $payPalPaymentMethodId = null;

    protected string $createOrderUrl;

    protected string $prepareCheckoutUrl;

    protected string $checkoutConfirmUrl;

    /**
     * @deprecated tag:v10.0.0 - Will be removed, use {@link handleErrorUrl} instead
     */
    protected string $addErrorUrl;

    protected string $handleErrorUrl;

    protected string $cancelRedirectUrl;

    protected bool $showPayLater;

    /**
     * @var string[]
     */
    protected array $fundingSources;

    public function getProductDetailEnabled(): bool
    {
        return $this->productDetailEnabled;
    }

    public function setProductDetailEnabled(bool $productDetailEnabled): void
    {
        $this->productDetailEnabled = $productDetailEnabled;
    }

    public function getOffCanvasEnabled(): bool
    {
        return $this->offCanvasEnabled;
    }

    public function setOffCanvasEnabled(bool $offCanvasEnabled): void
    {
        $this->offCanvasEnabled = $offCanvasEnabled;
    }

    public function getLoginEnabled(): bool
    {
        return $this->loginEnabled;
    }

    public function setLoginEnabled(bool $loginEnabled): void
    {
        $this->loginEnabled = $loginEnabled;
    }

    public function getListingEnabled(): bool
    {
        return $this->listingEnabled;
    }

    public function setListingEnabled(bool $listingEnabled): void
    {
        $this->listingEnabled = $listingEnabled;
    }

    public function getCartEnabled(): bool
    {
        return $this->cartEnabled;
    }

    public function setCartEnabled(bool $cartEnabled): void
    {
        $this->cartEnabled = $cartEnabled;
    }

    public function getButtonColor(): string
    {
        return $this->buttonColor;
    }

    public function setButtonColor(string $buttonColor): void
    {
        $this->buttonColor = $buttonColor;
    }

    public function getButtonShape(): string
    {
        return $this->buttonShape;
    }

    public function setButtonShape(string $buttonShape): void
    {
        $this->buttonShape = $buttonShape;
    }

    public function getAddProductToCart(): bool
    {
        return $this->addProductToCart;
    }

    public function setAddProductToCart(bool $addProductToCart): void
    {
        $this->addProductToCart = $addProductToCart;
    }

    public function getContextSwitchUrl(): string
    {
        return $this->contextSwitchUrl;
    }

    public function setContextSwitchUrl(string $contextSwitchUrl): void
    {
        $this->contextSwitchUrl = $contextSwitchUrl;
    }

    public function getPayPalPaymentMethodId(): ?string
    {
        return $this->payPalPaymentMethodId;
    }

    public function setPayPalPaymentMethodId(?string $payPalPaymentMethodId): void
    {
        $this->payPalPaymentMethodId = $payPalPaymentMethodId;
    }

    public function getCreateOrderUrl(): string
    {
        return $this->createOrderUrl;
    }

    public function setCreateOrderUrl(string $createOrderUrl): void
    {
        $this->createOrderUrl = $createOrderUrl;
    }

    public function getPrepareCheckoutUrl(): string
    {
        return $this->prepareCheckoutUrl;
    }

    public function setPrepareCheckoutUrl(string $prepareCheckoutUrl): void
    {
        $this->prepareCheckoutUrl = $prepareCheckoutUrl;
    }

    public function getCheckoutConfirmUrl(): string
    {
        return $this->checkoutConfirmUrl;
    }

    public function setCheckoutConfirmUrl(string $checkoutConfirmUrl): void
    {
        $this->checkoutConfirmUrl = $checkoutConfirmUrl;
    }

    /**
     * @deprecated tag:v10.0.0 - Will be removed, use {@link handleErrorUrl} instead
     */
    public function getAddErrorUrl(): string
    {
        return $this->addErrorUrl;
    }

    /**
     * @deprecated tag:v10.0.0 - Will be removed, use {@link handleErrorUrl} instead
     */
    public function setAddErrorUrl(string $addErrorUrl): void
    {
        $this->addErrorUrl = $addErrorUrl;
    }

    public function getHandleErrorUrl(): string
    {
        return $this->handleErrorUrl;
    }

    public function setHandleErrorUrl(string $handleErrorUrl): void
    {
        $this->handleErrorUrl = $handleErrorUrl;
    }

    public function getCancelRedirectUrl(): string
    {
        return $this->cancelRedirectUrl;
    }

    public function setCancelRedirectUrl(string $cancelRedirectUrl): void
    {
        $this->cancelRedirectUrl = $cancelRedirectUrl;
    }

    public function isShowPayLater(): bool
    {
        return $this->showPayLater;
    }

    public function setShowPayLater(bool $showPayLater): void
    {
        $this->showPayLater = $showPayLater;
    }

    public function getFundingSources(): array
    {
        return $this->fundingSources;
    }

    public function setFundingSources(array $fundingSources): void
    {
        $this->fundingSources = $fundingSources;
    }
}
