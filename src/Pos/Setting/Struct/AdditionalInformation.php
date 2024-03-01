<?php declare(strict_types=1);
/*
 * (c) shopware AG <info@shopware.com>
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Swag\PayPal\Pos\Setting\Struct;

use OpenApi\Attributes as OA;
use Shopware\Core\Framework\Log\Package;
use Shopware\Core\Framework\Struct\Struct;

#[OA\Schema(schema: 'swag_paypal_pos_setting_additional_information', properties: [new OA\Property(
    property: 'extensions',
    type: 'object',
    additionalProperties: true,
)])]
#[Package('checkout')]
class AdditionalInformation extends Struct
{
    #[OA\Property(type: 'string')]
    protected string $countryId;

    #[OA\Property(type: 'string')]
    protected string $currencyId;

    #[OA\Property(type: 'string', nullable: true)]
    protected ?string $languageId = null;

    #[OA\Property(type: 'string')]
    protected string $customerGroupId;

    #[OA\Property(type: 'string')]
    protected string $navigationCategoryId;

    #[OA\Property(type: 'string')]
    protected string $shippingMethodId;

    #[OA\Property(type: 'string')]
    protected string $paymentMethodId;

    #[OA\Property(type: 'array', items: new OA\Items(type: 'mixed'))]
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
