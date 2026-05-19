<?php declare(strict_types=1);
/*
 * (c) shopware AG <info@shopware.com>
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Swag\PayPal\Util\Lifecycle;

use Shopware\Core\Framework\Context;
use Shopware\Core\Framework\Log\Package;
use Swag\PayPal\Util\Lifecycle\State\AgenticCommerceService;
use Swag\PayPal\Util\Lifecycle\State\PaymentMethodStateService;
use Swag\PayPal\Util\Lifecycle\State\PosStateService;

/**
 * @internal
 */
#[Package('checkout')]
class ActivateDeactivate
{
    /**
     * @internal
     */
    public function __construct(
        private readonly PaymentMethodStateService $paymentMethodStateService,
        private readonly PosStateService $posStateService,
        private readonly AgenticCommerceService $agenticCommerceService,
    ) {
    }

    public function activate(Context $context): void
    {
        $this->paymentMethodStateService->setAllPaymentMethodsState(true, $context);
        $this->posStateService->addPosSalesChannelType($context);
        $this->agenticCommerceService->addAgenticSalesChannelType($context);
    }

    public function deactivate(Context $context): void
    {
        $this->paymentMethodStateService->setAllPaymentMethodsState(false, $context);
        $this->posStateService->deactivatePosSalesChannel($context);
        $this->agenticCommerceService->deactivateAgenticSalesChannelState($context);
    }
}
