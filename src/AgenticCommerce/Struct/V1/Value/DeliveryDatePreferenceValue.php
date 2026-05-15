<?php declare(strict_types=1);
/*
 * (c) shopware AG <info@shopware.com>
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Swag\PayPal\AgenticCommerce\Struct\V1\Value;

use OpenApi\Attributes as OA;
use Shopware\Core\Framework\Log\Package;
use Swag\PayPal\RestApi\PayPalApiStruct;

/**
 * @experimental
 */
#[Package('checkout')]
#[OA\Schema(schema: 'paypal_agentic_commerce_v1_value_delivery_date_preference_value')]
class DeliveryDatePreferenceValue extends PayPalApiStruct
{
    public const TIME_WINDOW__MORNING = 'morning';
    public const TIME_WINDOW__AFTERNOON = 'afternoon';
    public const TIME_WINDOW__EVENING = 'evening';
    public const TIME_WINDOW__ANYTIME = 'anytime';

    /**
     * Preferred delivery date
     */
    #[OA\Property(type: 'string')]
    protected ?string $preferredDate = null;

    /**
     * Preferred time window
     */
    #[OA\Property(
        type: 'string',
        enum: [self::TIME_WINDOW__MORNING, self::TIME_WINDOW__AFTERNOON, self::TIME_WINDOW__EVENING, self::TIME_WINDOW__ANYTIME],
    )]
    protected ?string $timeWindow = null;

    /**
     * Specific preferred time (HH:MM format)
     */
    #[OA\Property(
        type: 'string',
        pattern: '^([0-1]?[0-9]|2[0-3]):[0-5][0-9]$'
    )]
    protected ?string $specificTime = null;

    public function getPreferredDate(): ?string
    {
        return $this->preferredDate;
    }

    public function setPreferredDate(?string $preferredDate): void
    {
        if (!\in_array($preferredDate, [self::TIME_WINDOW__MORNING, self::TIME_WINDOW__AFTERNOON, self::TIME_WINDOW__EVENING, self::TIME_WINDOW__ANYTIME], true)) {
            throw new \InvalidArgumentException('PreferredDate must be a valid date');
        }

        $this->preferredDate = $preferredDate;
    }

    public function getTimeWindow(): ?string
    {
        return $this->timeWindow;
    }

    public function setTimeWindow(?string $timeWindow): void
    {
        $this->timeWindow = $timeWindow;
    }

    public function getSpecificTime(): ?string
    {
        return $this->specificTime;
    }

    public function setSpecificTime(?string $specificTime): void
    {
        $this->specificTime = $specificTime;
    }
}
