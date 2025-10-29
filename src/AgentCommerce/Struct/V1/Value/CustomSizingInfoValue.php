<?php declare(strict_types=1);
/*
 * (c) shopware AG <info@shopware.com>
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Swag\PayPal\AgentCommerce\Struct\V1\Value;

use OpenApi\Attributes as OA;
use Shopware\Core\Framework\Log\Package;
use Swag\PayPal\AgentCommerce\Struct\V1\Referral\Measurements;
use Swag\PayPal\RestApi\PayPalApiStruct;

/**
 * @experimental
 */
#[Package('checkout')]
#[OA\Schema(schema: 'paypal_agentic_commerce_v1_value_custom_sizing_info_value')]
class CustomSizingInfoValue extends PayPalApiStruct
{
    public const SIZE__TIGHT = 'tight';
    public const SIZE__REGULAR = 'regular';
    public const SIZE__LOOSE = 'loose';

    /**
     * Body measurements
     */
    #[OA\Property(ref: Measurements::class)]
    protected Measurements $measurements;

    /**
     * Fit preference
     */
    #[OA\Property(
        type: 'string',
        enum: [self::SIZE__TIGHT, self::SIZE__REGULAR, self::SIZE__LOOSE],
    )]
    protected ?string $sizePreference = null;

    /**
     * Special sizing requirements
     */
    #[OA\Property(type: 'string')]
    protected ?string $specialRequirements = null;

    public function getMeasurements(): Measurements
    {
        return $this->measurements;
    }

    public function setMeasurements(Measurements $measurements): void
    {
        $this->measurements = $measurements;
    }

    public function getSizePreference(): ?string
    {
        return $this->sizePreference;
    }

    public function setSizePreference(?string $sizePreference): void
    {
        $this->sizePreference = $sizePreference;
    }

    public function getSpecialRequirements(): ?string
    {
        return $this->specialRequirements;
    }

    public function setSpecialRequirements(?string $specialRequirements): void
    {
        $this->specialRequirements = $specialRequirements;
    }
}
