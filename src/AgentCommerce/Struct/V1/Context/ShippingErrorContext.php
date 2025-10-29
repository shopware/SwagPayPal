<?php declare(strict_types=1);
/*
 * (c) shopware AG <info@shopware.com>
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Swag\PayPal\AgentCommerce\Struct\V1\Context;

use OpenApi\Attributes as OA;
use Shopware\Core\Framework\Log\Package;
use Swag\PayPal\AgentCommerce\Struct\V1\Referral\SuggestedCorrection;
use Swag\PayPal\AgentCommerce\Struct\V1\Referral\SuggestedCorrectionCollection;

/**
 * @experimental
 */
#[Package('checkout')]
#[OA\Schema(schema: 'paypal_agentic_commerce_v1_context_shipping_error_context')]
class ShippingErrorContext extends AbstractContext
{
    public const ISSUE__MISSING_SHIPPING_ADDRESS = 'MISSING_SHIPPING_ADDRESS';
    public const ISSUE__SHIPPING_ADDRESS_INVALID = 'SHIPPING_ADDRESS_INVALID';
    public const ISSUE__SHIPPING_TO_PO_BOX_NOT_ALLOWED = 'SHIPPING_TO_PO_BOX_NOT_ALLOWED';
    public const ISSUE__NO_SHIPPING_OPTIONS = 'NO_SHIPPING_OPTIONS';
    public const ISSUE__INTERNATIONAL_SHIPPING_RESTRICTED = 'INTERNATIONAL_SHIPPING_RESTRICTED';
    public const ISSUE__REGION_RESTRICTED = 'REGION_RESTRICTED';
    public const ISSUE__OVERSIZED_ITEM_SHIPPING = 'OVERSIZED_ITEM_SHIPPING';
    public const ISSUE__HAZARDOUS_MATERIAL_SHIPPING = 'HAZARDOUS_MATERIAL_SHIPPING';
    public const ISSUE__SHIPPING_ZONE_NOT_COVERED = 'SHIPPING_ZONE_NOT_COVERED';
    public const ISSUE__MISSING_COORDINATES_FOR_ENHANCED_DELIVERY = 'MISSING_COORDINATES_FOR_ENHANCED_DELIVERY';

    public const RESTRICTED_REASON__SIGNATURE_REQUIRED = 'signature_required';
    public const RESTRICTED_REASON__AGE_VERIFICATION_REQUIRED = 'age_verification_required';
    public const RESTRICTED_REASON__EXPORT_CONTROLLED = 'export_controlled';
    public const RESTRICTED_REASON__HAZARDOUS_MATERIAL = 'hazardous_material';
    public const RESTRICTED_REASON__OVERSIZED_ITEM = 'oversized_item';
    public const RESTRICTED_REASON__PO_BOX_RESTRICTION = 'po_box_restriction';

    public const RESTRICTED_REASONS = [
        self::RESTRICTED_REASON__SIGNATURE_REQUIRED,
        self::RESTRICTED_REASON__AGE_VERIFICATION_REQUIRED,
        self::RESTRICTED_REASON__EXPORT_CONTROLLED,
        self::RESTRICTED_REASON__HAZARDOUS_MATERIAL,
        self::RESTRICTED_REASON__OVERSIZED_ITEM,
        self::RESTRICTED_REASON__PO_BOX_RESTRICTION,
    ];

    /**
     * Specific address validation failures
     *
     * @var string[]
     */
    #[OA\Property(
        type: 'array',
        items: new OA\Items(type: 'string'),
    )]
    protected ?array $validationFailures = null;

    /**
     * Suggested address corrections
     */
    #[OA\Property(type: 'array', items: new OA\Items(ref: SuggestedCorrection::class))]
    protected SuggestedCorrectionCollection $suggestedCorrections;

    /**
     * Address validation quality score
     */
    #[OA\Property(
        type: 'float',
        maximum: 1,
        minimum: 0,
    )]
    protected ?float $addressQualityScore = null;

    /**
     * Items with shipping restrictions
     *
     * @var string[]
     */
    #[OA\Property(
        type: 'array',
        items: new OA\Items(type: 'string'),
    )]
    protected ?array $restrictedItems = null;

    /**
     * Reason for shipping restriction
     */
    #[OA\Property(
        type: 'string',
        enum: self::RESTRICTED_REASONS,
    )]
    protected ?string $restrictionReason = null;

    /**
     * Whether PO Box was detected
     */
    #[OA\Property(type: 'boolean')]
    protected ?bool $poBoxDetected = null;

    /**
     * Destination country code
     */
    #[OA\Property(type: 'string')]
    protected ?string $destinationCountry = null;

    /**
     * Restricted region identifier
     */
    #[OA\Property(type: 'string')]
    protected ?string $restrictedRegion = null;

    /**
     * List of supported countries
     *
     * @var string[]
     */
    #[OA\Property(
        type: 'array',
        items: new OA\Items(type: 'string'),
    )]
    protected ?array $supportedCountries = null;

    /**
     * Address string that failed validation
     */
    #[OA\Property(type: 'string')]
    protected ?string $providedAddress = null;

    /**
     * @return ?string[]
     */
    public function getValidationFailures(): ?array
    {
        return $this->validationFailures;
    }

    /**
     * @param ?string[] $validationFailures
     */
    public function setValidationFailures(?array $validationFailures): void
    {
        $this->validationFailures = $validationFailures;
    }

    public function addValidationFailure(string $validationFailure): void
    {
        $this->validationFailures[] = $validationFailure;
    }

    public function getSuggestedCorrections(): SuggestedCorrectionCollection
    {
        return $this->suggestedCorrections;
    }

    public function setSuggestedCorrections(SuggestedCorrectionCollection $suggestedCorrections): void
    {
        $this->suggestedCorrections = $suggestedCorrections;
    }

    public function addSuggestedCorrection(SuggestedCorrection $suggestedCorrection): void
    {
        $this->suggestedCorrections->add($suggestedCorrection);
    }

    public function getAddressQualityScore(): ?float
    {
        return $this->addressQualityScore;
    }

    public function setAddressQualityScore(?float $addressQualityScore): void
    {
        if ($addressQualityScore < 0 || $addressQualityScore > 1) {
            throw new \InvalidArgumentException(\sprintf('Address quality score "%s" is not valid. Must be between 0 and 1', $addressQualityScore));
        }

        $this->addressQualityScore = $addressQualityScore;
    }

    /**
     * @return ?string[]
     */
    public function getRestrictedItems(): ?array
    {
        return $this->restrictedItems;
    }

    /**
     * @param ?string[] $restrictedItems
     */
    public function setRestrictedItems(?array $restrictedItems): void
    {
        $this->restrictedItems = $restrictedItems;
    }

    public function addRestrictedItem(string $restrictedItem): void
    {
        $this->restrictedItems[] = $restrictedItem;
    }

    public function getRestrictionReason(): ?string
    {
        return $this->restrictionReason;
    }

    public function setRestrictionReason(string $restrictionReason): void
    {
        if (!\in_array($restrictionReason, self::RESTRICTED_REASONS, true)) {
            throw new \InvalidArgumentException(\sprintf('Restricted reason "%s" is not valid.', $restrictionReason));
        }

        $this->restrictionReason = $restrictionReason;
    }

    public function getPoBoxDetected(): ?bool
    {
        return $this->poBoxDetected;
    }

    public function setPoBoxDetected(?bool $poBoxDetected): void
    {
        $this->poBoxDetected = $poBoxDetected;
    }

    public function getDestinationCountry(): ?string
    {
        return $this->destinationCountry;
    }

    public function setDestinationCountry(?string $destinationCountry): void
    {
        $this->destinationCountry = $destinationCountry;
    }

    public function getRestrictedRegion(): ?string
    {
        return $this->restrictedRegion;
    }

    public function setRestrictedRegion(?string $restrictedRegion): void
    {
        $this->restrictedRegion = $restrictedRegion;
    }

    /**
     * @return ?string[]
     */
    public function getSupportedCountries(): ?array
    {
        return $this->supportedCountries;
    }

    /**
     * @param ?string[] $supportedCountries
     */
    public function setSupportedCountries(?array $supportedCountries): void
    {
        $this->supportedCountries = $supportedCountries;
    }

    public function addSupportedCountries(string $supportedCountry): void
    {
        $this->supportedCountries[] = $supportedCountry;
    }

    public function getProvidedAddress(): ?string
    {
        return $this->providedAddress;
    }

    public function setProvidedAddress(?string $providedAddress): void
    {
        $this->providedAddress = $providedAddress;
    }

    protected static function getSpecificIssues(): array
    {
        return [
            self::ISSUE__MISSING_SHIPPING_ADDRESS,
            self::ISSUE__SHIPPING_ADDRESS_INVALID,
            self::ISSUE__SHIPPING_TO_PO_BOX_NOT_ALLOWED,
            self::ISSUE__NO_SHIPPING_OPTIONS,
            self::ISSUE__INTERNATIONAL_SHIPPING_RESTRICTED,
            self::ISSUE__REGION_RESTRICTED,
            self::ISSUE__OVERSIZED_ITEM_SHIPPING,
            self::ISSUE__HAZARDOUS_MATERIAL_SHIPPING,
            self::ISSUE__SHIPPING_ZONE_NOT_COVERED,
            self::ISSUE__MISSING_COORDINATES_FOR_ENHANCED_DELIVERY,
        ];
    }
}
