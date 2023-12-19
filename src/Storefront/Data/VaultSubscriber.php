<?php declare(strict_types=1);
/*
 * (c) shopware AG <info@shopware.com>
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Swag\PayPal\Storefront\Data;

use Shopware\Core\Framework\Log\Package;
use Shopware\Core\System\SystemConfig\SystemConfigService;
use Shopware\Storefront\Page\Account\Order\AccountEditOrderPageLoadedEvent;
use Shopware\Storefront\Page\Checkout\Confirm\CheckoutConfirmPageLoadedEvent;
use Shopware\Storefront\Page\PageLoadedEvent;
use Swag\PayPal\Setting\Exception\PayPalSettingsInvalidException;
use Swag\PayPal\Setting\Service\SettingsValidationServiceInterface;
use Swag\PayPal\Setting\Settings;
use Swag\PayPal\Storefront\Data\Service\VaultDataService;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;

/**
 * @internal
 */
#[Package('checkout')]
class VaultSubscriber implements EventSubscriberInterface
{
    public const VAULT_EXTENSION = 'swagPayPalVault';

    public function __construct(
        private SettingsValidationServiceInterface $settingsValidationService,
        private SystemConfigService $systemConfigService,
        private VaultDataService $vaultDataService,
    ) {
    }

    public static function getSubscribedEvents(): array
    {
        return [
            AccountEditOrderPageLoadedEvent::class => ['addVaultData', 20],
            CheckoutConfirmPageLoadedEvent::class => ['addVaultData', 20],
        ];
    }

    public function addVaultData(PageLoadedEvent $event): void
    {
        try {
            $this->settingsValidationService->validate($event->getSalesChannelContext()->getSalesChannelId());
        } catch (PayPalSettingsInvalidException) {
            return;
        }

        if (!$this->systemConfigService->getBool(Settings::VAULTING_ENABLED, $event->getSalesChannelContext()->getSalesChannelId())) {
            return;
        }

        $data = $this->vaultDataService->buildData($event->getSalesChannelContext());
        if ($data === null) {
            return;
        }

        $event->getPage()->addExtension(self::VAULT_EXTENSION, $data);
    }
}
