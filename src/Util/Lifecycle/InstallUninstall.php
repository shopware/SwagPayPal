<?php declare(strict_types=1);
/*
 * (c) shopware AG <info@shopware.com>
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Swag\PayPal\Util\Lifecycle;

use Shopware\Core\Framework\Context;
use Shopware\Core\Framework\Log\Package;
use Swag\PayPal\Util\Lifecycle\Installer\PaymentMethodInstaller;
use Swag\PayPal\Util\Lifecycle\Installer\PosInstaller;
use Swag\PayPal\Util\Lifecycle\Installer\SettingsInstaller;
use Swag\PayPal\Util\Lifecycle\State\PosStateService;

/**
 * @internal
 */
#[Package('checkout')]
class InstallUninstall
{
    private PaymentMethodInstaller $paymentMethodInstaller;

    private SettingsInstaller $settingsInstaller;

    private PosInstaller $posInstaller;

    private PosStateService $posStateService;

    public function __construct(
        PaymentMethodInstaller $paymentMethodInstaller,
        SettingsInstaller $settingsInstaller,
        PosInstaller $posInstaller,
        PosStateService $posStateService
    ) {
        $this->paymentMethodInstaller = $paymentMethodInstaller;
        $this->settingsInstaller = $settingsInstaller;
        $this->posInstaller = $posInstaller;
        $this->posStateService = $posStateService;
    }

    public function install(Context $context): void
    {
        $this->settingsInstaller->addDefaultConfiguration();
        $this->paymentMethodInstaller->installAll($context);
    }

    public function uninstall(Context $context): void
    {
        $this->posStateService->checkPosSalesChannels($context);
        $this->settingsInstaller->removeConfiguration($context);
        $this->posStateService->removePosSalesChannelType($context);
        $this->posStateService->removePosDefaultEntities($context);
        $this->posInstaller->removePosTables();
    }
}
