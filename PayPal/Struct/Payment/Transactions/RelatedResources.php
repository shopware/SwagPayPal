<?php declare(strict_types=1);
/**
 * (c) shopware AG <info@shopware.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace SwagPayPal\PayPal\Struct\Payment\Transactions;

use SwagPayPal\PayPal\Struct\Payment\RelatedResources\Authorization;
use SwagPayPal\PayPal\Struct\Payment\RelatedResources\Capture;
use SwagPayPal\PayPal\Struct\Payment\RelatedResources\Order;
use SwagPayPal\PayPal\Struct\Payment\RelatedResources\Refund;
use SwagPayPal\PayPal\Struct\Payment\RelatedResources\RelatedResource;
use SwagPayPal\PayPal\Struct\Payment\RelatedResources\ResourceType;
use SwagPayPal\PayPal\Struct\Payment\RelatedResources\Sale;

class RelatedResources
{
    /**
     * @var RelatedResource[]
     */
    private $resources;

    /**
     * @return RelatedResource[]
     */
    public function getResources(): array
    {
        return $this->resources;
    }

    /**
     * @param RelatedResource[] $resources
     */
    public function setResources(array $resources): void
    {
        $this->resources = $resources;
    }

    /**
     * @param array[] $data
     */
    public static function fromArray(array $data): RelatedResources
    {
        $result = new self();

        /** @var RelatedResource[] $relatedResources */
        $relatedResources = [];

        foreach ($data as $resource) {
            foreach ($resource as $key => $sale) {
                switch ($key) {
                    case ResourceType::SALE:
                        $relatedResources[] = Sale::fromArray($sale);
                        break;
                    case ResourceType::AUTHORIZATION:
                        $relatedResources[] = Authorization::fromArray($sale);
                        break;

                    case ResourceType::REFUND:
                        $relatedResources[] = Refund::fromArray($sale);
                        break;

                    case ResourceType::CAPTURE:
                        $relatedResources[] = Capture::fromArray($sale);
                        break;

                    case ResourceType::ORDER:
                        $relatedResources[] = Order::fromArray($sale);
                        break;
                }
            }
        }

        $result->setResources($relatedResources);

        return $result;
    }
}
