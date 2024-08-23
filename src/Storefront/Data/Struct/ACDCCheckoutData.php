<?php declare(strict_types=1);
/*
 * (c) shopware AG <info@shopware.com>
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Swag\PayPal\Storefront\Data\Struct;

use Shopware\Core\Framework\Log\Package;
use Swag\PayPal\Storefront\Data\Struct\ACDC\CardholderData;

#[Package('checkout')]
class ACDCCheckoutData extends AbstractCheckoutData
{
    /**
     * @deprecated tag:v10.0.0 - will be removed without replacement
     */
    protected CardholderData $cardholderData;

    protected array $billingAddress;

    protected string $billingAddressId;

    protected string $shippingAddressId;

    protected string $modifyAddressUrl;

    protected string $customerEmail;

    /**
     * @deprecated tag:v10.0.0 - will be removed without replacement
     */
    public function getCardholderData(): CardholderData
    {
        return $this->cardholderData;
    }

    /**
     * @deprecated tag:v10.0.0 - will be removed without replacement
     */
    public function setCardholderData(CardholderData $cardholderData): void
    {
        $this->cardholderData = $cardholderData;
    }

    public function getBillingAddress(): array
    {
        return $this->billingAddress;
    }

    public function setBillingAddress(array $billingAddress): void
    {
        $this->billingAddress = $billingAddress;
    }

    public function getBillingAddressId(): string
    {
        return $this->billingAddressId;
    }

    public function setBillingAddressId(string $billingAddressId): void
    {
        $this->billingAddressId = $billingAddressId;
    }

    public function getShippingAddressId(): string
    {
        return $this->shippingAddressId;
    }

    public function setShippingAddressId(string $shippingAddressId): void
    {
        $this->shippingAddressId = $shippingAddressId;
    }

    public function getModifyAddressUrl(): string
    {
        return $this->modifyAddressUrl;
    }

    public function setModifyAddressUrl(string $modifyAddressUrl): void
    {
        $this->modifyAddressUrl = $modifyAddressUrl;
    }

    public function getCustomerEmail(): string
    {
        return $this->customerEmail;
    }

    public function setCustomerEmail(string $customerEmail): void
    {
        $this->customerEmail = $customerEmail;
    }
}
