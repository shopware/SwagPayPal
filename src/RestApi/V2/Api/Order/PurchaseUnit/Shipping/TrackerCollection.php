<?php declare(strict_types=1);
/*
 * (c) shopware AG <info@shopware.com>
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Swag\PayPal\RestApi\V2\Api\Order\PurchaseUnit\Shipping;

use Shopware\Core\Framework\Log\Package;
use Swag\PayPal\RestApi\PayPalApiCollection;

/**
 * @extends PayPalApiCollection<Tracker>
 */
#[Package('checkout')]
class TrackerCollection extends PayPalApiCollection
{
    public static function getExpectedClass(): string
    {
        return Tracker::class;
    }

    /**
     * @return string[]
     */
    public function getTrackerCodes(): array
    {
        $trackerCodes = [];
        foreach ($this->elements as $tracker) {
            \strtok($tracker->getId(), '-');
            $code = \strtok('');

            if ($code && $tracker->getStatus() === Tracker::STATUS_SHIPPED) {
                $trackerCodes[] = $code;
            }
        }

        return $trackerCodes;
    }
}
