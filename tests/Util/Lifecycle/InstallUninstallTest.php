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
use Swag\PayPal\Util\Lifecycle\Installer\PaymentMethodInstaller;
use Swag\PayPal\Util\Lifecycle\Installer\PosInstaller;
use Swag\PayPal\Util\Lifecycle\Installer\SettingsInstaller;
use Swag\PayPal\Util\Lifecycle\InstallUninstall;
use Swag\PayPal\Util\Lifecycle\State\AgenticCommerceService;
use Swag\PayPal\Util\Lifecycle\State\PosStateService;

/**
 * @internal
 */
#[Package('checkout')]
class InstallUninstallTest extends TestCase
{
    public function testInstallAddsDefaultConfigurationAndPaymentMethods(): void
    {
        $context = Context::createDefaultContext();
        $paymentMethodInstaller = $this->createMock(PaymentMethodInstaller::class);
        $settingsInstaller = $this->createMock(SettingsInstaller::class);
        $posInstaller = $this->createMock(PosInstaller::class);
        $posStateService = $this->createMock(PosStateService::class);
        $agenticCommerceService = $this->createMock(AgenticCommerceService::class);

        $settingsInstaller
            ->expects($this->once())
            ->method('addDefaultConfiguration');
        $paymentMethodInstaller
            ->expects($this->once())
            ->method('installAll')
            ->with(static::identicalTo($context));

        (new InstallUninstall(
            $paymentMethodInstaller,
            $settingsInstaller,
            $posInstaller,
            $posStateService,
            $agenticCommerceService,
        ))->install($context);
    }

    public function testUninstallRemovesPosTypeAndDefaultsWhenNoPosSalesChannelsExist(): void
    {
        $context = Context::createDefaultContext();
        $paymentMethodInstaller = $this->createMock(PaymentMethodInstaller::class);
        $settingsInstaller = $this->createMock(SettingsInstaller::class);
        $posInstaller = $this->createMock(PosInstaller::class);
        $posStateService = $this->createMock(PosStateService::class);
        $agenticCommerceService = $this->createMock(AgenticCommerceService::class);

        $agenticCommerceService
            ->expects($this->once())
            ->method('handleUninstallAgentic')
            ->with(static::identicalTo($context));
        $posStateService
            ->expects($this->once())
            ->method('posSalesChannelsExists')
            ->with(static::identicalTo($context))
            ->willReturn(false);
        $posStateService
            ->expects($this->once())
            ->method('removePosSalesChannelType')
            ->with(static::identicalTo($context));
        $posStateService
            ->expects($this->once())
            ->method('removePosDefaultEntities')
            ->with(static::identicalTo($context));
        $settingsInstaller
            ->expects($this->once())
            ->method('removeConfiguration')
            ->with(static::identicalTo($context));
        $posInstaller
            ->expects($this->once())
            ->method('removePosTables');

        (new InstallUninstall(
            $paymentMethodInstaller,
            $settingsInstaller,
            $posInstaller,
            $posStateService,
            $agenticCommerceService,
        ))->uninstall($context);
    }

    public function testUninstallKeepsPosTypeAndDefaultsWhenPosSalesChannelsExist(): void
    {
        $context = Context::createDefaultContext();
        $paymentMethodInstaller = $this->createMock(PaymentMethodInstaller::class);
        $settingsInstaller = $this->createMock(SettingsInstaller::class);
        $posInstaller = $this->createMock(PosInstaller::class);
        $posStateService = $this->createMock(PosStateService::class);
        $agenticCommerceService = $this->createMock(AgenticCommerceService::class);

        $agenticCommerceService
            ->expects($this->once())
            ->method('handleUninstallAgentic')
            ->with(static::identicalTo($context));
        $posStateService
            ->expects($this->once())
            ->method('posSalesChannelsExists')
            ->with(static::identicalTo($context))
            ->willReturn(true);
        $posStateService
            ->expects($this->never())
            ->method('removePosSalesChannelType');
        $posStateService
            ->expects($this->never())
            ->method('removePosDefaultEntities');
        $settingsInstaller
            ->expects($this->once())
            ->method('removeConfiguration')
            ->with(static::identicalTo($context));
        $posInstaller
            ->expects($this->once())
            ->method('removePosTables');

        (new InstallUninstall(
            $paymentMethodInstaller,
            $settingsInstaller,
            $posInstaller,
            $posStateService,
            $agenticCommerceService,
        ))->uninstall($context);
    }
}
