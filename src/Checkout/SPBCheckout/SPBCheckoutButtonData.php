<?php declare(strict_types=1);
/*
 * (c) shopware AG <info@shopware.com>
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Swag\PayPal\Checkout\SPBCheckout;

use Shopware\Core\Framework\Struct\Struct;

class SPBCheckoutButtonData extends Struct
{
    /**
     * @var string
     */
    protected $clientId;

    /**
     * @var string
     */
    protected $languageIso;

    /**
     * @var string
     */
    protected $currency;

    /**
     * @var string
     */
    protected $intent;

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
    protected $paymentMethodId;

    /**
     * @var bool
     */
    protected $useAlternativePaymentMethods;

    /**
     * @var string
     */
    protected $createPaymentUrl;

    /**
     * @var string
     */
    protected $checkoutConfirmUrl;

    /**
     * @var string
     */
    protected $addErrorUrl;

    /**
     * @var string
     *
     * @deprecated tag:v2.0.0 - Will be removed without replacement
     */
    protected $errorParameter = SPBCheckoutSubscriber::PAYPAL_SMART_PAYMENT_BUTTONS_ERROR_PARAMETER;

    /**
     * @var string|null
     */
    protected $orderId;

    /**
     * @var string|null
     */
    protected $accountOrderEditUrl;

    public function getClientId(): string
    {
        return $this->clientId;
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

    public function getCreatePaymentUrl(): string
    {
        return $this->createPaymentUrl;
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
     * @deprecated tag:v2.0.0 - Will be removed without replacement
     */
    public function getErrorParameter(): string
    {
        return $this->errorParameter;
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
