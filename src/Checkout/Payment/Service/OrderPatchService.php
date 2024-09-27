<?php declare(strict_types=1);
/*
 * (c) shopware AG <info@shopware.com>
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Swag\PayPal\Checkout\Payment\Service;

use Shopware\Core\Checkout\Order\Aggregate\OrderTransaction\OrderTransactionEntity;
use Shopware\Core\Checkout\Order\OrderEntity;
use Shopware\Core\Framework\Log\Package;
use Shopware\Core\System\SalesChannel\SalesChannelContext;
use Shopware\Core\System\SystemConfig\SystemConfigService;
use Swag\PayPal\OrdersApi\Patch\PurchaseUnitPatchBuilder;
use Swag\PayPal\RestApi\Exception\PayPalApiException;
use Swag\PayPal\RestApi\V2\Resource\OrderResource;
use Swag\PayPal\Setting\Settings;

#[Package('checkout')]
class OrderPatchService
{
    private SystemConfigService $systemConfigService;

    private PurchaseUnitPatchBuilder $purchaseUnitPatchBuilder;

    private OrderResource $orderResource;

    /**
     * @internal
     */
    public function __construct(
        SystemConfigService $systemConfigService,
        PurchaseUnitPatchBuilder $purchaseUnitPatchBuilder,
        OrderResource $orderResource,
    ) {
        $this->systemConfigService = $systemConfigService;
        $this->purchaseUnitPatchBuilder = $purchaseUnitPatchBuilder;
        $this->orderResource = $orderResource;
    }

    /**
     * @throws PayPalApiException
     */
    public function patchOrder(
        OrderEntity $order,
        OrderTransactionEntity $orderTransaction,
        SalesChannelContext $salesChannelContext,
        string $paypalOrderId,
        string $partnerAttributionId,
    ): void {
        $patches = [
            $this->purchaseUnitPatchBuilder->createFinalPurchaseUnitPatch(
                $order,
                $orderTransaction,
                $salesChannelContext,
                $this->systemConfigService->getBool(Settings::SUBMIT_CART, $salesChannelContext->getSalesChannelId())
            ),
        ];

        $this->orderResource->update(
            $patches,
            $paypalOrderId,
            $salesChannelContext->getSalesChannelId(),
            $partnerAttributionId
        );
    }
}
