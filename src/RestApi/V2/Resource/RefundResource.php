<?php declare(strict_types=1);
/*
 * (c) shopware AG <info@shopware.com>
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Swag\PayPal\RestApi\V2\Resource;

use Shopware\Core\Framework\Log\Package;
use Shopware\PayPalSDK\Gateway\PaymentGateway;
use Shopware\PayPalSDK\Struct\V2\Order\PurchaseUnit\Payments\Refund;
use Swag\PayPal\RestApi\ApiContextFactoryInterface;
use Swag\PayPal\RestApi\Exception\PayPalApiException;

#[Package('checkout')]
class RefundResource
{
    /**
     * @internal
     */
    public function __construct(
        private readonly PaymentGateway $paymentGateway,
        private readonly ApiContextFactoryInterface $apiContextFactory,
    ) {
    }

    /**
     * @throws PayPalApiException
     */
    public function get(string $refundId, string $salesChannelId): Refund
    {
        return $this->paymentGateway->getRefund($refundId, $this->apiContextFactory->getApiContext($salesChannelId));
    }
}
