<?php declare(strict_types=1);
/*
 * (c) shopware AG <info@shopware.com>
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Swag\PayPal\Checkout\Plus;

use Shopware\Core\Framework\Struct\Struct;

class PlusData extends Struct
{
    /**
     * @deprecated tag:v4.0.0 - will be strongly typed
     *
     * @var string
     */
    protected $approvalUrl;

    /**
     * @deprecated tag:v4.0.0 - will be strongly typed
     *
     * @var string
     */
    protected $customerCountryIso;

    /**
     * @deprecated tag:v4.0.0 - will be strongly typed
     *
     * @var string
     */
    protected $mode;

    /**
     * @deprecated tag:v4.0.0 - will be strongly typed
     *
     * @var string
     */
    protected $customerSelectedLanguage;

    /**
     * @deprecated tag:v4.0.0 - will be strongly typed
     *
     * @var string
     */
    protected $paymentMethodId;

    /**
     * @deprecated tag:v4.0.0 - will be strongly typed
     *
     * @var string
     */
    protected $paypalPaymentId;

    /**
     * @deprecated tag:v4.0.0 - will be strongly typed
     *
     * @var string
     */
    protected $paypalToken;

    /**
     * @deprecated tag:v4.0.0 - will be strongly typed
     *
     * @var string
     */
    protected $checkoutOrderUrl;

    /**
     * @deprecated tag:v4.0.0 - will be strongly typed
     *
     * @var string
     */
    protected $handlePaymentUrl;

    /**
     * @deprecated tag:v4.0.0 - will be strongly typed
     *
     * @var string
     */
    protected $setPaymentRouteUrl;

    /**
     * @deprecated tag:v4.0.0 - will be strongly typed
     *
     * @var string
     */
    protected $contextSwitchUrl;

    /**
     * @deprecated tag:v4.0.0 - will be strongly typed
     *
     * @var string
     */
    protected $isEnabledParameterName;

    /**
     * @deprecated tag:v4.0.0 - will be strongly typed
     *
     * @var string
     */
    protected $languageId;

    /**
     * @deprecated tag:v4.0.0 - will be strongly typed
     *
     * @var string|null
     */
    protected $orderId;

    public function getApprovalUrl(): string
    {
        return $this->approvalUrl;
    }

    public function getCustomerCountryIso(): string
    {
        return $this->customerCountryIso;
    }

    public function getMode(): string
    {
        return $this->mode;
    }

    public function getCustomerSelectedLanguage(): string
    {
        return $this->customerSelectedLanguage;
    }

    public function getPaymentMethodId(): string
    {
        return $this->paymentMethodId;
    }

    public function getPaypalPaymentId(): string
    {
        return $this->paypalPaymentId;
    }

    public function getPaypalToken(): string
    {
        return $this->paypalToken;
    }

    public function getCheckoutOrderUrl(): string
    {
        return $this->checkoutOrderUrl;
    }

    public function getHandlePaymentUrl(): string
    {
        return $this->handlePaymentUrl;
    }

    public function getSetPaymentRouteUrl(): string
    {
        return $this->setPaymentRouteUrl;
    }

    public function getContextSwitchUrl(): string
    {
        return $this->contextSwitchUrl;
    }

    public function getIsEnabledParameterName(): string
    {
        return $this->isEnabledParameterName;
    }

    public function getLanguageId(): string
    {
        return $this->languageId;
    }

    public function getOrderId(): ?string
    {
        return $this->orderId;
    }

    public function setOrderId(?string $orderId): void
    {
        $this->orderId = $orderId;
    }
}
