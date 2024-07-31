<?php declare(strict_types=1);
/*
 * (c) shopware AG <info@shopware.com>
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Swag\PayPal\Checkout\ExpressCheckout;

use Swag\PayPal\Storefront\Data\Struct\AbstractCheckoutData;

class ExpressCheckoutButtonData extends AbstractCheckoutData
{
    protected bool $productDetailEnabled;

    protected bool $offCanvasEnabled;

    protected bool $loginEnabled;

    protected bool $listingEnabled;

    protected bool $cartEnabled;

    protected bool $addProductToCart;

    protected string $contextSwitchUrl;

    protected ?string $payPalPaymentMethodId = null;

    protected string $prepareCheckoutUrl;

    protected string $checkoutConfirmUrl;

    protected string $cancelRedirectUrl;

    /**
     * @deprecated tag:v8.0.0 - will be removed, use "showPayLater" instead
     */
    protected bool $disablePayLater;

    protected bool $showPayLater;

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

    public function getCartEnabled(): bool
    {
        return $this->cartEnabled;
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

    public function getPrepareCheckoutUrl(): string
    {
        return $this->prepareCheckoutUrl;
    }

    public function getCheckoutConfirmUrl(): string
    {
        return $this->checkoutConfirmUrl;
    }

    public function getCancelRedirectUrl(): string
    {
        return $this->cancelRedirectUrl;
    }

    /**
     * @deprecated tag:v8.0.0 - will be removed, use "showPayLater" instead
     */
    public function isDisablePayLater(): bool
    {
        return $this->disablePayLater;
    }

    /**
     * @deprecated tag:v8.0.0 - will be removed, use "showPayLater" instead
     */
    public function setDisablePayLater(bool $disablePayLater): void
    {
        $this->disablePayLater = $disablePayLater;
    }

    public function isShowPayLater(): bool
    {
        return $this->showPayLater;
    }

    public function setShowPayLater(bool $showPayLater): void
    {
        $this->showPayLater = $showPayLater;
    }
}
