<?php declare(strict_types=1);

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
     * @var bool
     */
    protected $useSandbox;

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

    public function getUseSandbox(): bool
    {
        return $this->useSandbox;
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
}
