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
    /**
     * @var bool
     */
    protected $productDetailEnabled;

    /**
     * @var bool
     */
    protected $offCanvasEnabled;

    /**
     * @var bool
     */
    protected $loginEnabled;

    /**
     * @var bool
     */
    protected $listingEnabled;

    /**
     * @var string
     */
    protected $buttonColor;

    /**
     * @var string
     */
    protected $buttonShape;

    /**
     * @var string
     */
    protected $languageIso;

    /**
     * @var bool
     */
    protected $cartEnabled;

    /**
     * @var string
     */
    protected $clientId;

    /**
     * @var string
     */
    protected $currency;

    /**
     * @var string
     */
    protected $intent;

    /**
     * @var bool
     */
    protected $addProductToCart;

    /**
     * @var string
     */
    protected $contextSwitchUrl;

    /**
     * @var string|null
     */
    protected $payPaLPaymentMethodId;

    /**
     * @var string
     */
    protected $createOrderUrl;

    /**
     * @var string
     */
    protected $deleteCartUrl;

    /**
     * @var string
     */
    protected $prepareCheckoutUrl;

    /**
     * @var string
     */
    protected $checkoutConfirmUrl;

    /**
     * @var string
     */
    protected $addErrorUrl;

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

    public function getPayPaLPaymentMethodId(): ?string
    {
        return $this->payPaLPaymentMethodId;
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
}
