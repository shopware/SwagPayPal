<?php declare(strict_types=1);
/*
 * (c) shopware AG <info@shopware.com>
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Swag\PayPal\Util\Lifecycle;

use Shopware\Core\Framework\Context;
use Shopware\Core\Framework\Log\Package;
use Swag\PayPal\Util\Lifecycle\State\PaymentMethodStateService;
use Swag\PayPal\Util\Lifecycle\State\PosStateService;

/**
 * @internal
 */
#[Package('checkout')]
class ActivateDeactivate
{
    private PaymentMethodStateService $paymentMethodStateService;

    private PosStateService $posStateService;

    /**
     * @internal
     */
    public function __construct(
        PaymentMethodStateService $paymentMethodStateService,
        PosStateService $posStateService,
    ) {
        $this->paymentMethodStateService = $paymentMethodStateService;
        $this->posStateService = $posStateService;
    }

    public function activate(Context $context): void
    {
        $this->paymentMethodStateService->setAllPaymentMethodsState(true, $context);
        $this->posStateService->addPosSalesChannelType($context);
    }

    public function deactivate(Context $context): void
    {
        $this->paymentMethodStateService->setAllPaymentMethodsState(false, $context);
        $this->posStateService->checkPosSalesChannels($context);
        $this->posStateService->removePosSalesChannelType($context);
        $this->posStateService->removePosDefaultEntities($context);
    }
}
