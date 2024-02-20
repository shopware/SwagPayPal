<?php declare(strict_types=1);
/*
 * (c) shopware AG <info@shopware.com>
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Swag\PayPal\RestApi\V1\Api;

use OpenApi\Attributes as OA;
use Shopware\Core\Framework\Log\Package;
use Swag\PayPal\RestApi\PayPalApiStruct;
use Swag\PayPal\RestApi\V1\Api\Shipping\Tracker;
use Swag\PayPal\RestApi\V1\Api\Shipping\TrackerCollection;

#[OA\Schema(schema: 'swag_paypal_v1_shipping')]
#[Package('checkout')]
class Shipping extends PayPalApiStruct
{
    #[OA\Property(type: 'array', items: new OA\Items(ref: Tracker::class))]
    protected TrackerCollection $trackers;

    public function getTrackers(): TrackerCollection
    {
        return $this->trackers;
    }

    public function setTrackers(TrackerCollection $trackers): void
    {
        $this->trackers = $trackers;
    }
}
