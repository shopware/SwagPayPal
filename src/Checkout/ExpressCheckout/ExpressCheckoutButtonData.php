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
     * @deprecated tag:v3.0.0 - will be removed. Use createOrderUrl instead
     *
     * @var string
     */
    protected $createPaymentUrl;

    /**
     * @var string
     */
    protected $createOrderUrl;

    /**
     * @deprecated tag:v3.0.0 - will be removed. Use deleteCartUrl instead
     *
     * @var string
     */
    protected $createNewCartUrl;

    /**
     * @var string
     */
    protected $deleteCartUrl;

    /**
     * @deprecated tag:v3.0.0 - will be removed. Use prepareCheckoutUrl instead
     *
     * @var string
     */
    protected $approvePaymentUrl;

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

    /**
     * @deprecated tag:v3.0.0 - will be removed. Increase the min Shopware version to 6.3.2.0
     *
     * @var bool
     */
    protected $useStoreApi;

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

    /**
     * @deprecated tag:v3.0.0 - will be removed. Use getCreateOrderUrl instead
     */
    public function getCreatePaymentUrl(): string
    {
        return $this->createPaymentUrl;
    }

    public function getCreateOrderUrl(): string
    {
        return $this->createOrderUrl;
    }

    /**
     * @deprecated tag:v3.0.0 - will be removed. Use getDeleteCartUrl instead
     */
    public function getCreateNewCartUrl(): string
    {
        return $this->createNewCartUrl;
    }

    public function getDeleteCartUrl(): string
    {
        return $this->deleteCartUrl;
    }

    /**
     * @deprecated tag:v3.0.0 - will be removed. Use getCheckoutConfirmUrl instead
     */
    public function getApprovePaymentUrl(): string
    {
        return $this->approvePaymentUrl;
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

    /**
     * @deprecated tag:v3.0.0 - will be removed. Increase the min Shopware version to 6.3.2.0
     */
    public function getUseStoreApi(): bool
    {
        return $this->useStoreApi;
    }
}
