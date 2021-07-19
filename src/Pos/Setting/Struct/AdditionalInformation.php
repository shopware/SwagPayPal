<?php declare(strict_types=1);
/*
 * (c) shopware AG <info@shopware.com>
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Swag\PayPal\Pos\Setting\Struct;

use Shopware\Core\Framework\Struct\Struct;

class AdditionalInformation extends Struct
{
    /**
     * @deprecated tag:v4.0.0 - will be strongly typed
     *
     * @var string
     */
    protected $countryId;

    /**
     * @deprecated tag:v4.0.0 - will be strongly typed
     *
     * @var string
     */
    protected $currencyId;

    /**
     * @deprecated tag:v4.0.0 - will be strongly typed
     *
     * @var string|null
     */
    protected $languageId;

    /**
     * @deprecated tag:v4.0.0 - will be strongly typed
     *
     * @var string
     */
    protected $customerGroupId;

    /**
     * @deprecated tag:v4.0.0 - will be strongly typed
     *
     * @var string
     */
    protected $navigationCategoryId;

    /**
     * @deprecated tag:v4.0.0 - will be strongly typed
     *
     * @var string
     */
    protected $shippingMethodId;

    /**
     * @deprecated tag:v4.0.0 - will be strongly typed
     *
     * @var string
     */
    protected $paymentMethodId;

    /**
     * @deprecated tag:v4.0.0 - will be strongly typed
     *
     * @var array
     */
    protected $merchantInformation;

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
