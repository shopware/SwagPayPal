<?php declare(strict_types=1);
/*
 * (c) shopware AG <info@shopware.com>
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Swag\PayPal\RestApi\V1\Api\Plan\BillingCycle;

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
#[OA\Schema(schema: 'swag_paypal_v1_plan_billing_cycle_frequency')]
#[Package('checkout')]
class Frequency extends PayPalApiStruct
{
    #[OA\Property(type: 'string')]
    protected string $intervalUnit;

    #[OA\Property(type: 'integer')]
    protected int $intervalCount;

    public function getIntervalUnit(): string
    {
        return $this->intervalUnit;
    }

    public function setIntervalUnit(string $intervalUnit): void
    {
        $this->intervalUnit = $intervalUnit;
    }

    public function getIntervalCount(): int
    {
        return $this->intervalCount;
    }

    public function setIntervalCount(int $intervalCount): void
    {
        $this->intervalCount = $intervalCount;
    }
}
