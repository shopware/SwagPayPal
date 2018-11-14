<?php declare(strict_types=1);
/**
 * (c) shopware AG <info@shopware.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace SwagPayPal\PayPal\Struct\Payment\RelatedResources;

class Order extends RelatedResource
{
    public static function fromArray(array $data): Order
    {
        $result = new self();
        $result->prepare($result, $data, ResourceType::ORDER);

        return $result;
    }
}
