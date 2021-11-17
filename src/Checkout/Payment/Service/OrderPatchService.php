<?php declare(strict_types=1);
/*
 * (c) shopware AG <info@shopware.com>
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Swag\PayPal\Checkout\Payment\Service;

use Shopware\Core\System\SystemConfig\SystemConfigService;
use Swag\PayPal\OrdersApi\Patch\CustomIdPatchBuilder;
use Swag\PayPal\OrdersApi\Patch\OrderNumberPatchBuilder;
use Swag\PayPal\RestApi\Exception\PayPalApiException;
use Swag\PayPal\RestApi\V2\Resource\OrderResource;
use Swag\PayPal\Setting\Settings;

class OrderPatchService
{
    private CustomIdPatchBuilder $customIdPatchBuilder;

    private SystemConfigService $systemConfigService;

    private OrderNumberPatchBuilder $orderNumberPatchBuilder;

    private OrderResource $orderResource;

    public function __construct(
        CustomIdPatchBuilder $customIdPatchBuilder,
        SystemConfigService $systemConfigService,
        OrderNumberPatchBuilder $orderNumberPatchBuilder,
        OrderResource $orderResource
    ) {
        $this->customIdPatchBuilder = $customIdPatchBuilder;
        $this->systemConfigService = $systemConfigService;
        $this->orderNumberPatchBuilder = $orderNumberPatchBuilder;
        $this->orderResource = $orderResource;
    }

    /**
     * @throws PayPalApiException
     */
    public function patchOrderData(
        string $orderTransactionId,
        ?string $orderNumber,
        string $paypalOrderId,
        string $partnerAttributionId,
        string $salesChannelId
    ): void {
        $patches = [$this->customIdPatchBuilder->createCustomIdPatch($orderTransactionId)];

        if ($orderNumber !== null && $this->systemConfigService->getBool(Settings::SEND_ORDER_NUMBER, $salesChannelId)) {
            $orderNumberPrefix = $this->systemConfigService->getString(Settings::ORDER_NUMBER_PREFIX, $salesChannelId);
            $orderNumberSuffix = $this->systemConfigService->getString(Settings::ORDER_NUMBER_SUFFIX, $salesChannelId);
            $orderNumber = $orderNumberPrefix . $orderNumber . $orderNumberSuffix;
            $patches[] = $this->orderNumberPatchBuilder->createOrderNumberPatch($orderNumber);
        }

        $this->orderResource->update(
            $patches,
            $paypalOrderId,
            $salesChannelId,
            $partnerAttributionId
        );
    }
}
