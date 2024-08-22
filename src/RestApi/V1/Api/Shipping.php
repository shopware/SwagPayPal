<?php declare(strict_types=1);
/*
 * (c) shopware AG <info@shopware.com>
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Swag\PayPal\RestApi\V1\Api;

use OpenApi\Annotations as OA;
use Shopware\Core\Framework\Log\Package;
use Swag\PayPal\RestApi\PayPalApiStruct;
use Swag\PayPal\RestApi\V1\Api\Shipping\Tracker;

/**
 * @OA\Schema(schema="swag_paypal_v1_shipping_batch")
 */
#[Package('checkout')]
class Shipping extends PayPalApiStruct
{
    /**
     * @OA\Property(type="array", items={"$ref": "#/components/schemas/swag_paypal_v1_common_link"})
     *
     * @var Tracker[]
     */
    protected array $trackers;

    public function getTrackers(): array
    {
        return $this->trackers;
    }

    public function setTrackers(array $trackers): void
    {
        $this->trackers = $trackers;
    }
}
