<?php declare(strict_types=1);
/*
 * (c) shopware AG <info@shopware.com>
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Swag\PayPal\Storefront\Data\Struct;

use Shopware\Core\Framework\Log\Package;

#[Package('checkout')]
class AbstractCheckoutData extends AbstractScriptData
{
    protected string $buttonShape;

    protected string $buttonColor;

    protected ?string $userIdToken = null;

    protected string $paymentMethodId;

    protected string $createOrderUrl;

    /**
     * @deprecated tag:v10.0.0 - Will be removed, use {@link handleErrorUrl} instead
     */
    protected string $addErrorUrl;

    protected string $handleErrorUrl;

    protected bool $preventErrorReload;

    protected ?string $orderId = null;

    protected ?string $accountOrderEditCancelledUrl = null;

    protected ?string $accountOrderEditFailedUrl = null;

    protected string $brandName;

    public function getUserIdToken(): ?string
    {
        return $this->userIdToken;
    }

    public function setUserIdToken(?string $userIdToken): void
    {
        $this->userIdToken = $userIdToken;
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

    public function getButtonColor(): string
    {
        return $this->buttonColor;
    }

    public function setButtonColor(string $buttonColor): void
    {
        $this->buttonColor = $buttonColor;
    }

    public function getBrandName(): string
    {
        return $this->brandName;
    }

    public function setBrandName(string $brandName): void
    {
        $this->brandName = $brandName;
    }
}
