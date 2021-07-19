<?php declare(strict_types=1);
/*
 * (c) shopware AG <info@shopware.com>
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Swag\PayPal\RestApi\V2\Api\Order\PurchaseUnit;

use Swag\PayPal\RestApi\V2\Api\Common\Money;
use Swag\PayPal\RestApi\V2\Api\Order\PurchaseUnit\Amount\Breakdown;

class Amount extends Money
{
    /**
     * @deprecated tag:v4.0.0 - will be strongly typed
     *
     * @var Breakdown|null
     */
    protected $breakdown;

    public function getBreakdown(): ?Breakdown
    {
        return $this->breakdown;
    }

    public function setBreakdown(?Breakdown $breakdown): void
    {
        $this->breakdown = $breakdown;
    }
}
