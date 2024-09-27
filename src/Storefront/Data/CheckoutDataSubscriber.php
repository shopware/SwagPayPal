<?php declare(strict_types=1);
/*
 * (c) shopware AG <info@shopware.com>
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Swag\PayPal\Storefront\Data;

use Psr\Log\LoggerInterface;
use Shopware\Core\Checkout\Cart\Cart;
use Shopware\Core\Checkout\Order\OrderEntity;
use Shopware\Core\Framework\Log\Package;
use Shopware\Core\System\SalesChannel\SalesChannelContext;
use Shopware\Storefront\Page\Account\Order\AccountEditOrderPageLoadedEvent;
use Shopware\Storefront\Page\Checkout\Confirm\CheckoutConfirmPageLoadedEvent;
use Shopware\Storefront\Page\PageLoadedEvent;
use Swag\PayPal\Setting\Exception\PayPalSettingsInvalidException;
use Swag\PayPal\Setting\Service\SettingsValidationServiceInterface;
use Swag\PayPal\Storefront\Controller\PayPalController;
use Swag\PayPal\Storefront\Data\Event\PayPalPageExtensionAddedEvent;
use Swag\PayPal\Storefront\Data\Service\AbstractCheckoutDataService;
use Swag\PayPal\Storefront\Data\Struct\VaultData;
use Swag\PayPal\Util\Lifecycle\Method\ACDCMethodData;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Contracts\EventDispatcher\EventDispatcherInterface;

/**
 * @internal
 */
#[Package('checkout')]
class CheckoutDataSubscriber implements EventSubscriberInterface
{
    public function __construct(
        private readonly LoggerInterface $logger,
        private readonly SettingsValidationServiceInterface $settingsValidationService,
        private readonly RequestStack $requestStack,
        private readonly EventDispatcherInterface $eventDispatcher,
        private ?iterable $apmCheckoutMethods = null,
    ) {
        if ($this->apmCheckoutMethods !== null) {
            if (!\is_array($this->apmCheckoutMethods)) {
                $this->apmCheckoutMethods = [...$this->apmCheckoutMethods];
            }
        }
    }

    public static function getSubscribedEvents(): array
    {
        return [
            AccountEditOrderPageLoadedEvent::class => ['onAccountOrderEditLoaded', 10],
            CheckoutConfirmPageLoadedEvent::class => ['onCheckoutConfirmLoaded', 10],
            'subscription.' . CheckoutConfirmPageLoadedEvent::class => ['onCheckoutConfirmLoaded', 10],
        ];
    }

    public function onAccountOrderEditLoaded(AccountEditOrderPageLoadedEvent $event): void
    {
        if ($this->apmCheckoutMethods === null) {
            return;
        }

        foreach ($this->apmCheckoutMethods as $checkoutMethod) {
            if (!$this->checkSettings($event->getSalesChannelContext(), $checkoutMethod->getHandler())) {
                continue;
            }

            $vaultData = $event->getPage()->getExtensionOfType(VaultSubscriber::VAULT_EXTENSION, VaultData::class);
            if ($vaultData?->getIdentifier() && $checkoutMethod instanceof ACDCMethodData) {
                return;
            }

            $this->addExtension($checkoutMethod, $event, null, $event->getPage()->getOrder());
        }
    }

    public function onCheckoutConfirmLoaded(CheckoutConfirmPageLoadedEvent $event): void
    {
        if ($this->apmCheckoutMethods === null || $event->getPage()->getCart()->getErrors()->blockOrder()) {
            return;
        }

        foreach ($this->apmCheckoutMethods as $checkoutMethod) {
            if (!$this->checkSettings($event->getSalesChannelContext(), $checkoutMethod->getHandler())) {
                continue;
            }

            $vaultData = $event->getPage()->getExtensionOfType(VaultSubscriber::VAULT_EXTENSION, VaultData::class);
            if ($vaultData?->getIdentifier() && $checkoutMethod instanceof ACDCMethodData) {
                return;
            }

            $this->addExtension($checkoutMethod, $event, $event->getPage()->getCart());
        }
    }

    /**
     * @param class-string $handler
     */
    private function checkSettings(SalesChannelContext $salesChannelContext, string $handler): bool
    {
        if ($salesChannelContext->getPaymentMethod()->getHandlerIdentifier() !== $handler) {
            return false;
        }

        try {
            $this->settingsValidationService->validate($salesChannelContext->getSalesChannelId());
        } catch (PayPalSettingsInvalidException $e) {
            return false;
        }

        return true;
    }

    /**
     * @param CheckoutConfirmPageLoadedEvent|AccountEditOrderPageLoadedEvent $event
     */
    private function addExtension(CheckoutDataMethodInterface $methodData, PageLoadedEvent $event, ?Cart $cart = null, ?OrderEntity $order = null): void
    {
        $page = $event->getPage();
        if ($page->hasExtension($methodData->getCheckoutTemplateExtensionId())) {
            return;
        }

        $this->logger->debug('Adding data');
        $checkoutData = $methodData->getCheckoutDataService()->buildCheckoutData($event->getSalesChannelContext(), $cart, $order);

        if (!$checkoutData) {
            return;
        }

        $checkoutData->setPreventErrorReload($this->isErrorReload($event->getSalesChannelContext()));

        $page->addExtension($methodData->getCheckoutTemplateExtensionId(), $checkoutData);
        $this->eventDispatcher->dispatch(new PayPalPageExtensionAddedEvent($page, $methodData, $checkoutData));

        $this->logger->debug('Added data');
    }

    /**
     * Checks if a PayPal error was added via {@link PayPalController::onHandleError}
     * and sets the preventErrorReload property of the $checkoutData accordingly.
     */
    private function isErrorReload(SalesChannelContext $context): bool
    {
        $request = $this->requestStack->getCurrentRequest();
        if ($request !== null && $request->query->has(AbstractCheckoutDataService::PAYPAL_ERROR)) {
            return true;
        }

        $session = $this->requestStack->getSession();

        $paymentMethodId = $session->get(PayPalController::PAYMENT_METHOD_FATAL_ERROR);
        $session->remove(PayPalController::PAYMENT_METHOD_FATAL_ERROR);
        if ($paymentMethodId === $context->getPaymentMethod()->getId()) {
            return true;
        }

        return false;
    }
}
