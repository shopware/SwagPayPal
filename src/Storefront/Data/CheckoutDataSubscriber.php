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
use Swag\PayPal\Storefront\Data\Event\PayPalPageExtensionAddedEvent;
use Swag\PayPal\Storefront\Data\Service\AbstractCheckoutDataService;
use Swag\PayPal\Storefront\Data\Struct\VaultData;
use Swag\PayPal\Util\Lifecycle\Method\ACDCMethodData;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Contracts\EventDispatcher\EventDispatcherInterface;
use Symfony\Contracts\Translation\TranslatorInterface;

/**
 * @internal
 */
#[Package('checkout')]
class CheckoutDataSubscriber implements EventSubscriberInterface
{
    private LoggerInterface $logger;

    private SettingsValidationServiceInterface $settingsValidationService;

    private RequestStack $requestStack;

    private TranslatorInterface $translator;

    private EventDispatcherInterface $eventDispatcher;

    private ?iterable $apmCheckoutMethods;

    public function __construct(
        LoggerInterface $logger,
        SettingsValidationServiceInterface $settingsValidationService,
        RequestStack $requestStack,
        TranslatorInterface $translator,
        EventDispatcherInterface $eventDispatcher,
        ?iterable $apmCheckoutMethods = null
    ) {
        $this->logger = $logger;
        $this->settingsValidationService = $settingsValidationService;
        $this->requestStack = $requestStack;
        $this->translator = $translator;
        $this->eventDispatcher = $eventDispatcher;
        $this->apmCheckoutMethods = $apmCheckoutMethods;

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
        $this->logger->debug('Adding data');
        $checkoutData = $methodData->getCheckoutDataService()->buildCheckoutData($event->getSalesChannelContext(), $cart, $order);

        if (!$checkoutData) {
            return;
        }

        $checkoutData->setPreventErrorReload($this->isErrorReload());

        $page = $event->getPage();
        $page->addExtension($methodData->getCheckoutTemplateExtensionId(), $checkoutData);
        $this->eventDispatcher->dispatch(new PayPalPageExtensionAddedEvent($page, $methodData, $checkoutData));

        $this->logger->debug('Added data');
    }

    /**
     * Checks if a PayPal error was added via Swag\PayPal\Checkout\SalesChannel\ErrorRoute::addErrorMessage
     * and sets the preventErrorReload property of the $checkoutData accordingly.
     */
    private function isErrorReload(): bool
    {
        $request = $this->requestStack->getCurrentRequest();
        if ($request !== null && $request->query->has(AbstractCheckoutDataService::PAYPAL_ERROR)) {
            return true;
        }

        $session = $this->requestStack->getSession();
        if (!\method_exists($session, 'getFlashBag')) {
            return false;
        }

        $flashes = $session->getFlashBag()->peekAll();

        $possibleMessages = [
            $this->translator->trans('paypal.general.paymentError'),
            $this->translator->trans('paypal.general.browserUnsupported'),
        ];

        foreach ($flashes as $val) {
            if (\is_array($val) && \array_intersect($val, $possibleMessages)) {
                return true;
            }

            if (\is_string($val) && \in_array($val, $possibleMessages, true)) {
                return true;
            }
        }

        return false;
    }
}
