<?php declare(strict_types=1);
/*
 * (c) shopware AG <info@shopware.com>
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Swag\PayPal\AgentCommerce\Struct\V1;

use OpenApi\Attributes as OA;
use Shopware\Core\Framework\Log\Package;
use Swag\PayPal\RestApi\PayPalApiStruct;

/**
 * @experimental
 */
#[Package('checkout')]
#[OA\Schema(schema: 'paypal_agentic_commerce_v1_geo_coordinates')]
class GeoCoordinates extends PayPalApiStruct
{
    /**
     * Latitude coordinate in decimal degrees (-90 to 90). WGS84 datum.
     */
    #[OA\Property(
        type: 'string',
        pattern: '^-?([1-8]?[0-9](\.\d+)?|90(\.0+)?)$'
    )]
    protected ?string $latitude = null;

    /**
     * Longitude coordinate in decimal degrees (-180 to 180). WGS84 datum.
     */
    #[OA\Property(
        type: 'string',
        pattern: '^-?((1[0-7]|[1-9])?[0-9](\.\d+)?|180(\.0+)?)$'
    )]
    protected ?string $longitude = null;

    /**
     * Administrative subdivision code (state, province, region).
     * ISO 3166-2 format without country prefix (e.g., 'CA' for California, 'ON' for Ontario).
     */
    #[OA\Property(
        type: 'string',
        maxLength: 10,
        minLength: 1,
        pattern: '^[A-Z0-9-]+$'
    )]
    protected ?string $subdivision = null;

    /**
     * ISO 3166-1 alpha-2 country code for the coordinate location.
     */
    #[OA\Property(
        type: 'string',
        maxLength: 2,
        minLength: 2,
        pattern: '^[A-Z]{2}$'
    )]
    protected ?string $countryCode = null;

    public function getLatitude(): ?string
    {
        return $this->latitude;
    }

    public function setLatitude(?string $latitude): void
    {
        $this->latitude = $latitude;
    }

    public function getLongitude(): ?string
    {
        return $this->longitude;
    }

    public function setLongitude(?string $longitude): void
    {
        $this->longitude = $longitude;
    }

    public function getSubdivision(): ?string
    {
        return $this->subdivision;
    }

    public function setSubdivision(?string $subdivision): void
    {
        $this->subdivision = $subdivision;
    }

    public function getCountryCode(): ?string
    {
        return $this->countryCode;
    }

    public function setCountryCode(?string $countryCode): void
    {
        $this->countryCode = $countryCode;
    }

    public function jsonSerialize(): array
    {
        return \array_filter(parent::jsonSerialize());
    }
}
