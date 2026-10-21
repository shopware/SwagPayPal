<?php declare(strict_types=1);
/*
 * (c) shopware AG <info@shopware.com>
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Swag\PayPal\Storefront\Data;

use Shopware\Core\Framework\Log\Package;
use Shopware\Storefront\Page\Account\Order\AccountEditOrderPageLoadedEvent;
use Shopware\Storefront\Page\Checkout\Confirm\CheckoutConfirmPageLoadedEvent;
use Shopware\Storefront\Page\PageLoadedEvent;
use Swag\PayPal\Setting\Exception\PayPalSettingsInvalidException;
use Swag\PayPal\Setting\Service\SettingsValidationServiceInterface;
use Swag\PayPal\Storefront\Data\Service\SavedPaymentMethodDataService;
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
        private VaultDataService $vaultDataService,
        private SavedPaymentMethodDataService $savedPaymentMethodDataService,
    ) {
    }

    public static function getSubscribedEvents(): array
    {
        return [
            AccountEditOrderPageLoadedEvent::class => ['addVaultData', 20],
            CheckoutConfirmPageLoadedEvent::class => ['addVaultData', 20],
            'subscription.' . CheckoutConfirmPageLoadedEvent::class => ['addVaultData', 20],
        ];
    }

    /**
     * @param CheckoutConfirmPageLoadedEvent|AccountEditOrderPageLoadedEvent $event
     */
    public function addVaultData(PageLoadedEvent $event): void
    {
        try {
            $this->settingsValidationService->validate($event->getSalesChannelContext()->getSalesChannelId());
        } catch (PayPalSettingsInvalidException) {
            return;
        }

        $data = $this->vaultDataService->buildData($event->getSalesChannelContext());
        if ($data === null) {
            return;
        }

        if ($data->getIdentifier()) {
            $data->setSavedPaymentMethod($this->savedPaymentMethodDataService->buildCheckoutData(
                $event->getSalesChannelContext(),
                $event instanceof CheckoutConfirmPageLoadedEvent ? $event->getPage()->getCart() : null,
                $event instanceof AccountEditOrderPageLoadedEvent ? $event->getPage()->getOrder() : null,
            ));
        }

        $event->getPage()->addExtension(self::VAULT_EXTENSION, $data);
    }
}
