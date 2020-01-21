<?php declare(strict_types=1);
/*
 * (c) shopware AG <info@shopware.com>
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Swag\PayPal\Checkout\Plus;

use Shopware\Core\Checkout\Payment\PaymentMethodEntity;
use Shopware\Storefront\Page\Checkout\Confirm\CheckoutConfirmPageLoadedEvent;
use Shopware\Storefront\Page\Checkout\Finish\CheckoutFinishPageLoadedEvent;
use Swag\PayPal\Checkout\Plus\Service\PlusDataService;
use Swag\PayPal\Setting\Exception\PayPalSettingsInvalidException;
use Swag\PayPal\Setting\Service\SettingsServiceInterface;
use Swag\PayPal\Setting\SwagPayPalSettingStruct;
use Swag\PayPal\Util\PaymentMethodUtil;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Contracts\Translation\TranslatorInterface;

class PlusSubscriber implements EventSubscriberInterface
{
    /**
     * @var SettingsServiceInterface
     */
    private $settingsService;

    /**
     * @var PlusDataService
     */
    private $plusDataService;

    /**
     * @var PaymentMethodUtil
     */
    private $paymentMethodUtil;

    /**
     * @var TranslatorInterface
     */
    private $translator;

    public function __construct(
        SettingsServiceInterface $settingsService,
        PlusDataService $plusDataService,
        PaymentMethodUtil $paymentMethodUtil,
        TranslatorInterface $translator
    ) {
        $this->settingsService = $settingsService;
        $this->plusDataService = $plusDataService;
        $this->paymentMethodUtil = $paymentMethodUtil;
        $this->translator = $translator;
    }

    public static function getSubscribedEvents(): array
    {
        return [
            CheckoutConfirmPageLoadedEvent::class => 'onCheckoutConfirmLoaded',
            CheckoutFinishPageLoadedEvent::class => 'onCheckoutFinishLoaded',
        ];
    }

    public function onCheckoutConfirmLoaded(CheckoutConfirmPageLoadedEvent $event): void
    {
        $salesChannelContext = $event->getSalesChannelContext();
        if (!$this->paymentMethodUtil->isPaypalPaymentMethodInSalesChannel($salesChannelContext)) {
            return;
        }

        try {
            $settings = $this->settingsService->getSettings($salesChannelContext->getSalesChannel()->getId());
        } catch (PayPalSettingsInvalidException $e) {
            return;
        }

        if (!$settings->getPlusCheckoutEnabled()
            || $settings->getMerchantLocation() === SwagPayPalSettingStruct::MERCHANT_LOCATION_OTHER
        ) {
            return;
        }

        $plusData = $this->plusDataService->getPlusData($event->getPage()->getCart(), $salesChannelContext, $settings);

        if ($plusData === null) {
            return;
        }

        $this->changePaymentMethod($salesChannelContext->getPaymentMethod());

        $payPalPaymentId = $plusData->getPaymentMethodId();
        $payPalPaymentMethodFromCollection = $event->getPage()->getPaymentMethods()->get($payPalPaymentId);
        if ($payPalPaymentMethodFromCollection !== null) {
            $this->changePaymentMethod($payPalPaymentMethodFromCollection);
        }

        $event->getPage()->addExtension('payPalPlusData', $plusData);
    }

    public function onCheckoutFinishLoaded(CheckoutFinishPageLoadedEvent $event): void
    {
        $salesChannelContext = $event->getSalesChannelContext();
        try {
            $settings = $this->settingsService->getSettings($salesChannelContext->getSalesChannel()->getId());
        } catch (PayPalSettingsInvalidException $e) {
            return;
        }

        if (!$settings->getPlusCheckoutEnabled()
            || $settings->getMerchantLocation() === SwagPayPalSettingStruct::MERCHANT_LOCATION_OTHER
        ) {
            return;
        }

        $transactions = $event->getPage()->getOrder()->getTransactions();
        if ($transactions === null) {
            return;
        }

        $transaction = $transactions->first();
        if ($transaction === null) {
            return;
        }

        $paymentMethod = $transaction->getPaymentMethod();
        if ($paymentMethod === null) {
            return;
        }

        $this->changePaymentMethod($paymentMethod);
    }

    private function changePaymentMethod(PaymentMethodEntity $paymentMethod): void
    {
        $paymentMethod->addTranslated('name', $this->translator->trans('payPalPlus.paymentNameOverwrite'));

        $description = $paymentMethod->getTranslation('description');
        if ($description === null) {
            $description = $paymentMethod->getDescription();
        }

        $paymentMethod->addTranslated(
            'description',
            $description . ' ' . $this->translator->trans('payPalPlus.paymentDescriptionExtension')
        );
    }
}
