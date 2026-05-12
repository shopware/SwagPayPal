<?php declare(strict_types=1);
/*
 * (c) shopware AG <info@shopware.com>
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Swag\PayPal\Storefront\Data;

use Shopware\Core\Framework\Log\Package;
use Shopware\Core\System\SalesChannel\SalesChannelContext;
use Shopware\Storefront\Page\Checkout\Confirm\CheckoutConfirmPageLoadedEvent;
use Shopware\Storefront\Page\Checkout\Register\CheckoutRegisterPageLoadedEvent;
use Shopware\Storefront\Page\GenericPageLoadedEvent;
use Shopware\Storefront\Pagelet\Footer\FooterPageletLoadedEvent;
use Swag\PayPal\Checkout\SalesChannel\MethodEligibilityRoute;
use Swag\PayPal\Setting\Exception\PayPalSettingsInvalidException;
use Swag\PayPal\Setting\Service\SettingsValidationServiceInterface;
use Swag\PayPal\Storefront\Data\Service\FundingEligibilityDataService;
use Swag\PayPal\Storefront\Data\Struct\FundingEligibilityData;
use Swag\PayPal\Util\PaymentMethodUtil;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;

/**
 * @internal
 */
#[Package('checkout')]
class FundingSubscriber implements EventSubscriberInterface
{
    public const FUNDING_ELIGIBILITY_EXTENSION = 'swagPayPalFundingEligibility';

    public function __construct(
        private readonly SettingsValidationServiceInterface $settingsValidationService,
        private readonly FundingEligibilityDataService $fundingEligibilityDataService,
        private readonly PaymentMethodUtil $paymentMethodUtil,
    ) {
    }

    public static function getSubscribedEvents(): array
    {
        return [
            FooterPageletLoadedEvent::class => 'addFundingAvailabilityDataToFooter',  // for backward compability
            GenericPageLoadedEvent::class => 'addFundingAvailabilityDataToPage',
            CheckoutConfirmPageLoadedEvent::class => ['removeFundingAvailabilityDataFromPage', -1],
            CheckoutRegisterPageLoadedEvent::class => ['removeFundingAvailabilityDataFromPage', -1],
        ];
    }

    /**
     * @deprecated tag:v11.0.0 - Will be removed.
     */
    public function addFundingAvailabilityDataToFooter(FooterPageletLoadedEvent $event): void
    {
        $data = $this->getFundingEligiblityData($event->getSalesChannelContext());
        if ($data === null) {
            return;
        }

        $event->getPagelet()->addExtension(self::FUNDING_ELIGIBILITY_EXTENSION, $data);
    }

    public function addFundingAvailabilityDataToPage(GenericPageLoadedEvent $event): void
    {
        $data = $this->getFundingEligiblityData($event->getSalesChannelContext());
        if ($data === null) {
            return;
        }

        $event->getPage()->addExtension(self::FUNDING_ELIGIBILITY_EXTENSION, $data);
    }

    public function removeFundingAvailabilityDataFromPage(CheckoutConfirmPageLoadedEvent|CheckoutRegisterPageLoadedEvent $event): void
    {
        $event->getPage()->removeExtension(self::FUNDING_ELIGIBILITY_EXTENSION);
    }

    private function getFundingEligiblityData(SalesChannelContext $salesChannelContext): ?FundingEligibilityData
    {
        if (!$this->paymentMethodUtil->isPaymentMethodActive($salesChannelContext, \array_values(MethodEligibilityRoute::REMOVABLE_PAYMENT_HANDLERS))) {
            return null;
        }

        try {
            $this->settingsValidationService->validate($salesChannelContext->getSalesChannelId());
        } catch (PayPalSettingsInvalidException) {
            return null;
        }

        return $this->fundingEligibilityDataService->buildData($salesChannelContext);
    }
}
