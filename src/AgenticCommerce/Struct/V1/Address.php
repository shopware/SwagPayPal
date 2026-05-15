<?php declare(strict_types=1);
/*
 * (c) shopware AG <info@shopware.com>
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Swag\PayPal\AgenticCommerce\Struct\V1;

use OpenApi\Attributes as OA;
use Shopware\Core\Framework\Log\Package;
use Swag\PayPal\RestApi\PayPalApiStruct;

/**
 * @experimental
 */
#[Package('checkout')]
#[OA\Schema(
    schema: 'paypal_agentic_commerce_v1_address',
    required: ['countryCode']
)]
class Address extends PayPalApiStruct
{
    /**
     * The first line of the address, such as number and street, for example, 173 Drury Lane.
     * Needed for data entry, and Compliance and Risk checks. This field needs to pass the full address.
     */
    #[OA\Property(
        type: 'string',
        maxLength: 300,
        minLength: 0,
    )]
    protected ?string $addressLine_1 = null;

    #[OA\Property(
        type: 'string',
        maxLength: 300,
        minLength: 0,
    )]
    protected ?string $addressLine_2 = null;

    /**
     * The highest-level sub-division in a country, which is usually a province, state, or ISO-3166-2 subdivision.
     * This data is formatted for postal delivery, for example, CA and not California. Value, by country, is UK.
     * A county. US. A state. Canada. A province. Japan. A prefecture. Switzerland. A kanton.
     */
    #[OA\Property(
        type: 'string',
        maxLength: 300,
        minLength: 0,
    )]
    protected ?string $adminArea_1 = null;

    /**
     * A city, town, or village. Smaller than admin_area_level_1.
     */
    #[OA\Property(
        type: 'string',
        maxLength: 120,
        minLength: 0,
    )]
    protected ?string $adminArea_2 = null;

    /**
     * The postal code, which is the ZIP code or equivalent.
     * Typically required for countries with a postal code or an equivalent. See postal code.
     */
    #[OA\Property(
        type: 'string',
        maxLength: 60,
        minLength: 0,
    )]
    protected ?string $postalCode = null;

    /**
     * The 2-character ISO 3166-1 alpha-2 country code
     */
    #[OA\Property(
        type: 'string',
        maxLength: 2,
        minLength: 2,
        pattern: '^[A-Z]{2}$'
    )]
    protected string $countryCode;

    public function getAddressLine1(): ?string
    {
        return $this->addressLine_1;
    }

    public function setAddressLine1(?string $addressLine1): void
    {
        $this->addressLine_1 = $addressLine1;
    }

    public function getAddressLine2(): ?string
    {
        return $this->addressLine_2;
    }

    public function setAddressLine2(?string $addressLine2): void
    {
        $this->addressLine_2 = $addressLine2;
    }

    public function getAdminArea1(): ?string
    {
        return $this->adminArea_1;
    }

    public function setAdminArea1(?string $adminArea1): void
    {
        $this->adminArea_1 = $adminArea1;
    }

    public function getAdminArea2(): ?string
    {
        return $this->adminArea_2;
    }

    public function setAdminArea2(?string $adminArea2): void
    {
        $this->adminArea_2 = $adminArea2;
    }

    public function getPostalCode(): ?string
    {
        return $this->postalCode;
    }

    public function setPostalCode(?string $postalCode): void
    {
        $this->postalCode = $postalCode;
    }

    public function getCountryCode(): string
    {
        return $this->countryCode;
    }

    public function setCountryCode(string $countryCode): void
    {
        if (!preg_match('/^[A-Z]{2}$/', $countryCode)) {
            throw new \InvalidArgumentException(\sprintf('Country code "%s" is not valid.', $countryCode));
        }

        $this->countryCode = $countryCode;
    }

    public function jsonSerialize(): array
    {
        return \array_filter(parent::jsonSerialize());
    }
}
