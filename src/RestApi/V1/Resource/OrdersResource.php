<?php declare(strict_types=1);
/*
 * (c) shopware AG <info@shopware.com>
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Swag\PayPal\RestApi\V1\Resource;

use Shopware\Core\Framework\Log\Package;
use Shopware\PayPalSDK\Gateway\PaymentV1Gateway;
use Shopware\PayPalSDK\Struct\V1\Payment\Transaction\RelatedResource\Order;
use Swag\PayPal\RestApi\ApiContextFactoryInterface;
use Swag\PayPal\RestApi\Exception\PayPalApiException;

#[Package('checkout')]
class OrdersResource
{
    /**
     * @internal
     */
    public function __construct(
        private readonly PaymentV1Gateway $paymentGateway,
        private readonly ApiContextFactoryInterface $apiContextFactory,
    ) {
    }

    /**
     * @throws PayPalApiException
     */
    public function get(string $orderId, string $salesChannelId): Order
    {
        return $this->paymentGateway->getOrder($orderId, $this->apiContextFactory->getApiContext($salesChannelId));
    }
}
