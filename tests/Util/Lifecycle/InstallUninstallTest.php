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
use Swag\PayPal\Util\Lifecycle\Installer\TableInstaller;
use Swag\PayPal\Util\Lifecycle\InstallUninstall;
use Swag\PayPal\Util\Lifecycle\State\PosStateService;

/**
 * @internal
 *
 * @covers \Swag\PayPal\Util\Lifecycle\InstallUninstall
 */
#[Package('checkout')]
class InstallUninstallTest extends TestCase
{
    public function testUninstallRemovesThePluginTables(): void
    {
        $context = Context::createDefaultContext();
        $settingsInstaller = $this->createMock(SettingsInstaller::class);
        $settingsInstaller
            ->expects(static::once())
            ->method('removeConfiguration')
            ->with(static::identicalTo($context));
        $posInstaller = $this->createMock(PosInstaller::class);
        $posInstaller
            ->expects(static::once())
            ->method('removePosTables');
        $tableInstaller = $this->createMock(TableInstaller::class);
        $tableInstaller
            ->expects(static::once())
            ->method('removeTables');

        (new InstallUninstall(
            $this->createMock(PaymentMethodInstaller::class),
            $settingsInstaller,
            $posInstaller,
            $this->createMock(PosStateService::class),
            $tableInstaller
        ))->uninstall($context);
    }
}
