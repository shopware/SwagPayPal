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
     * @var string
     */
    protected $approvalUrl;

    /**
     * @var string
     */
    protected $customerCountryIso;

    /**
     * @var string
     */
    protected $mode;

    /**
     * @var string
     */
    protected $customerSelectedLanguage;

    /**
     * @var string
     */
    protected $paymentMethodId;

    /**
     * @var string
     */
    protected $paypalPaymentId;

    /**
     * @var string
     */
    protected $paypalToken;

    /**
     * @var string
     */
    protected $checkoutOrderUrl;

    /**
     * @var string
     */
    protected $setPaymentRouteUrl;

    /**
     * @var string
     */
    protected $isEnabledParameterName;

    /**
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

    public function getSetPaymentRouteUrl(): string
    {
        return $this->setPaymentRouteUrl;
    }

    public function getIsEnabledParameterName(): string
    {
        return $this->isEnabledParameterName;
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
