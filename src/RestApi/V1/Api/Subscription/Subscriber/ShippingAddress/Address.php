<?php
declare(strict_types=1);
/*
 * (c) shopware AG <info@shopware.com>
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Swag\PayPal\RestApi\V1\Api\Subscription\Subscriber\ShippingAddress;

use OpenApi\Attributes as OA;
use Shopware\Core\Framework\Log\Package;
use Swag\PayPal\RestApi\PayPalApiStruct;

/**
 * @codeCoverageIgnore
 *
 * @experimental
 *
 * This class is experimental and not officially supported.
 * It is currently not used within the plugin itself. Use with caution.
 */
#[OA\Schema(schema: 'swag_paypal_v1_subscription_subscriber_shipping_address_address')]
#[Package('checkout')]
class Address extends PayPalApiStruct
{
    #[OA\Property(type: 'string', nullable: true)]
    protected ?string $addressLine_1 = null;

    #[OA\Property(type: 'string', nullable: true)]
    protected ?string $addressLine_2 = null;

    #[OA\Property(type: 'string', nullable: true)]
    protected ?string $adminArea_1 = null;

    #[OA\Property(type: 'string', nullable: true)]
    protected ?string $adminArea_2 = null;

    #[OA\Property(type: 'string', nullable: true)]
    protected ?string $postalCode = null;

    #[OA\Property(type: 'string')]
    protected string $countryCode;

    public function getAddressLine1(): ?string
    {
        return $this->addressLine_1;
    }

    public function setAddressLine1(?string $addressLine_1): void
    {
        $this->addressLine_1 = $addressLine_1;
    }

    public function getAddressLine2(): ?string
    {
        return $this->addressLine_2;
    }

    public function setAddressLine2(?string $addressLine_2): void
    {
        $this->addressLine_2 = $addressLine_2;
    }

    public function getAdminArea1(): ?string
    {
        return $this->adminArea_1;
    }

    public function setAdminArea1(?string $adminArea_1): void
    {
        $this->adminArea_1 = $adminArea_1;
    }

    public function getAdminArea2(): ?string
    {
        return $this->adminArea_2;
    }

    public function setAdminArea2(?string $adminArea_2): void
    {
        $this->adminArea_2 = $adminArea_2;
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
        $this->countryCode = $countryCode;
    }
}
