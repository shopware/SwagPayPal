<?php

declare(strict_types=1);
/*
 * (c) shopware AG <info@shopware.com>
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Swag\PayPal\Pos\Setting\Struct;

use Shopware\Core\Framework\Log\Package;
use Shopware\Core\Framework\Struct\Struct;

#[Package('checkout')]
class AdditionalInformation extends Struct
{
    protected string $countryId;

    protected string $currencyId;

    protected ?string $languageId = null;

    protected string $customerGroupId;

    protected string $navigationCategoryId;

    protected string $shippingMethodId;

    protected string $paymentMethodId;

    protected array $merchantInformation;

    public function setCountryId(string $countryId): void
    {
        $this->countryId = $countryId;
    }

    public function setCurrencyId(string $currencyId): void
    {
        $this->currencyId = $currencyId;
    }

    public function setLanguageId(?string $languageId): void
    {
        $this->languageId = $languageId;
    }

    public function setCustomerGroupId(string $customerGroupId): void
    {
        $this->customerGroupId = $customerGroupId;
    }

    public function setNavigationCategoryId(string $navigationCategoryId): void
    {
        $this->navigationCategoryId = $navigationCategoryId;
    }

    public function setShippingMethodId(string $shippingMethodId): void
    {
        $this->shippingMethodId = $shippingMethodId;
    }

    public function setPaymentMethodId(string $paymentMethodId): void
    {
        $this->paymentMethodId = $paymentMethodId;
    }

    public function setMerchantInformation(array $merchantInformation): void
    {
        $this->merchantInformation = $merchantInformation;
    }
}
