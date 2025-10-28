<?php declare(strict_types=1);
/*
 * (c) shopware AG <info@shopware.com>
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Swag\PayPal\AgentCommerce\Struct\V1\Referral;

use OpenApi\Attributes as OA;
use Shopware\Core\Framework\Log\Package;
use Shopware\Core\Framework\Struct\Struct;

/**
 * @experimental
 */
#[Package('checkout')]
#[OA\Schema(schema: 'paypal_agentic_commerce_v1_referral_business_hour')]
class BusinessHour extends Struct
{
    #[OA\Property(type: 'string')]
    protected string $openTime;

    #[OA\Property(type: 'string')]
    protected string $closeTime;

    #[OA\Property(type: 'string')]
    protected string $timezone;

    public function getOpenTime(): string
    {
        return $this->openTime;
    }

    public function setOpenTime(string $openTime): void
    {
        $this->openTime = $openTime;
    }

    public function getCloseTime(): string
    {
        return $this->closeTime;
    }

    public function setCloseTime(string $closeTime): void
    {
        $this->closeTime = $closeTime;
    }

    public function getTimezone(): string
    {
        return $this->timezone;
    }

    public function setTimezone(string $timezone): void
    {
        $this->timezone = $timezone;
    }
}
