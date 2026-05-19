<?php declare(strict_types=1);
/*
 * (c) shopware AG <info@shopware.com>
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Swag\PayPal\Test\Util\Lifecycle;

use PHPUnit\Framework\TestCase;
use Shopware\Core\Framework\Context;
use Shopware\Core\Framework\Log\Package;
use Swag\PayPal\Util\Lifecycle\ActivateDeactivate;
use Swag\PayPal\Util\Lifecycle\State\AgenticCommerceService;
use Swag\PayPal\Util\Lifecycle\State\PaymentMethodStateService;
use Swag\PayPal\Util\Lifecycle\State\PosStateService;

/**
 * @internal
 */
#[Package('checkout')]
class ActivateDeactivateTest extends TestCase
{
    public function testActivateActivatesPaymentMethodsAndAddsSalesChannelTypes(): void
    {
        $context = Context::createDefaultContext();
        $paymentMethodStateService = $this->createMock(PaymentMethodStateService::class);
        $posStateService = $this->createMock(PosStateService::class);
        $agenticCommerceService = $this->createMock(AgenticCommerceService::class);

        $paymentMethodStateService
            ->expects($this->once())
            ->method('setAllPaymentMethodsState')
            ->with(true, static::identicalTo($context));
        $posStateService
            ->expects($this->once())
            ->method('addPosSalesChannelType')
            ->with(static::identicalTo($context));
        $agenticCommerceService
            ->expects($this->once())
            ->method('addAgenticSalesChannelType')
            ->with(static::identicalTo($context));

        (new ActivateDeactivate($paymentMethodStateService, $posStateService, $agenticCommerceService))->activate($context);
    }

    public function testDeactivateDeactivatesPaymentMethodsAndSalesChannels(): void
    {
        $context = Context::createDefaultContext();
        $paymentMethodStateService = $this->createMock(PaymentMethodStateService::class);
        $posStateService = $this->createMock(PosStateService::class);
        $agenticCommerceService = $this->createMock(AgenticCommerceService::class);

        $paymentMethodStateService
            ->expects($this->once())
            ->method('setAllPaymentMethodsState')
            ->with(false, static::identicalTo($context));
        $posStateService
            ->expects($this->once())
            ->method('deactivatePosSalesChannel')
            ->with(static::identicalTo($context));
        $agenticCommerceService
            ->expects($this->once())
            ->method('deactivateAgenticSalesChannelState')
            ->with(static::identicalTo($context));

        (new ActivateDeactivate($paymentMethodStateService, $posStateService, $agenticCommerceService))->deactivate($context);
    }
}
