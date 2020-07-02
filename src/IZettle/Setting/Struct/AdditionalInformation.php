<?php declare(strict_types=1);
/*
 * (c) shopware AG <info@shopware.com>
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Swag\PayPal\IZettle\Setting\Struct;

use Shopware\Core\Framework\Struct\Struct;

class AdditionalInformation extends Struct
{
    /**
     * @var string
     */
    protected $countryId;

    /**
     * @var string
     */
    protected $currencyId;

    /**
     * @var string
     */
    protected $languageId;

    /**
     * @var string
     */
    protected $customerGroupId;

    /**
     * @var string
     */
    protected $navigationCategoryId;

    /**
     * @var string
     */
    protected $shippingMethodId;

    /**
     * @var string
     */
    protected $paymentMethodId;

    public function setCountryId(string $countryId): void
    {
        $this->countryId = $countryId;
    }

    public function setCurrencyId(string $currencyId): void
    {
        $this->currencyId = $currencyId;
    }

    public function setLanguageId(string $languageId): void
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
}
