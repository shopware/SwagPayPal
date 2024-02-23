<?php declare(strict_types=1);
/*
 * (c) shopware AG <info@shopware.com>
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Swag\PayPal\RestApi\V1\Api\Common;

use OpenApi\Attributes as OA;
use Shopware\Core\Framework\Log\Package;
use Swag\PayPal\RestApi\PayPalApiStruct;

#[OA\Schema(schema: 'swag_paypal_v1_common_address')]
#[Package('checkout')]
class Address extends PayPalApiStruct
{
    #[OA\Property(type: 'string')]
    protected string $line1;

    #[OA\Property(type: 'string', nullable: true)]
    protected ?string $line2 = null;

    #[OA\Property(type: 'string')]
    protected string $city;

    #[OA\Property(type: 'string')]
    protected string $countryCode;

    #[OA\Property(type: 'string')]
    protected string $postalCode;

    #[OA\Property(type: 'string', nullable: true)]
    protected ?string $state = null;

    #[OA\Property(type: 'string', nullable: true)]
    protected ?string $phone = null;

    public function getLine1(): string
    {
        return $this->line1;
    }

    public function setLine1(string $line1): void
    {
        $this->line1 = $line1;
    }

    public function getLine2(): ?string
    {
        return $this->line2;
    }

    public function setLine2(string $line2): void
    {
        $this->line2 = $line2;
    }

    public function getCity(): string
    {
        return $this->city;
    }

    public function setCity(string $city): void
    {
        $this->city = $city;
    }

    public function getCountryCode(): string
    {
        return $this->countryCode;
    }

    public function setCountryCode(string $countryCode): void
    {
        $this->countryCode = $countryCode;
    }

    public function getPostalCode(): string
    {
        return $this->postalCode;
    }

    public function setPostalCode(string $postalCode): void
    {
        $this->postalCode = $postalCode;
    }

    public function getState(): ?string
    {
        return $this->state;
    }

    public function setState(?string $state): void
    {
        $this->state = $state;
    }

    public function getPhone(): ?string
    {
        return $this->phone;
    }

    public function setPhone(string $phone): void
    {
        $this->phone = $phone;
    }
}
