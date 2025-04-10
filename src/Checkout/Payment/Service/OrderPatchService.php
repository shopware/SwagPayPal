<?php declare(strict_types=1);
/*
 * (c) shopware AG <info@shopware.com>
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Swag\PayPal\Checkout\Payment\Service;

use Shopware\Core\Checkout\Order\Aggregate\OrderTransaction\OrderTransactionEntity;
use Shopware\Core\Checkout\Order\OrderEntity;
use Shopware\Core\Framework\Context;
use Shopware\Core\Framework\Log\Package;
use Shopware\Core\System\SystemConfig\SystemConfigService;
use Swag\PayPal\OrdersApi\Patch\PurchaseUnitPatchBuilder;
use Swag\PayPal\RestApi\Exception\PayPalApiException;
use Swag\PayPal\RestApi\V2\Resource\OrderResource;
use Swag\PayPal\Setting\Settings;

#[Package('checkout')]
class OrderPatchService
{
    /**
     * @internal
     */
    public function __construct(
        private readonly SystemConfigService $systemConfigService,
        private readonly PurchaseUnitPatchBuilder $purchaseUnitPatchBuilder,
        private readonly OrderResource $orderResource,
    ) {
    }

    /**
     * @throws PayPalApiException
     */
    public function patchOrder(
        OrderEntity $order,
        OrderTransactionEntity $orderTransaction,
        Context $context,
        string $paypalOrderId,
        string $partnerAttributionId,
    ): void {
        $patches = [
            $this->purchaseUnitPatchBuilder->createFinalPurchaseUnitPatch(
                $order,
                $orderTransaction,
                $context,
                $this->systemConfigService->getBool(Settings::SUBMIT_CART, $order->getSalesChannelId())
            ),
        ];

        $this->orderResource->update(
            $patches,
            $paypalOrderId,
            $order->getSalesChannelId(),
            $partnerAttributionId
        );
    }
}
