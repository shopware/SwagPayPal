<?php declare(strict_types=1);
/*
 * (c) shopware AG <info@shopware.com>
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Swag\PayPal\Storefront\Data;

use Shopware\Core\Framework\Log\Package;
use Shopware\Storefront\Pagelet\Footer\FooterPageletLoadedEvent;
use Swag\PayPal\Checkout\SalesChannel\MethodEligibilityRoute;
use Swag\PayPal\Setting\Exception\PayPalSettingsInvalidException;
use Swag\PayPal\Setting\Service\SettingsValidationServiceInterface;
use Swag\PayPal\Storefront\Data\Service\FundingEligibilityDataService;
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
            FooterPageletLoadedEvent::class => 'addFundingAvailabilityData',
        ];
    }

    public function addFundingAvailabilityData(FooterPageletLoadedEvent $event): void
    {
        if (!$this->paymentMethodUtil->isPaymentMethodActive($event->getSalesChannelContext(), \array_values(MethodEligibilityRoute::REMOVABLE_PAYMENT_HANDLERS))) {
            return;
        }

        try {
            $this->settingsValidationService->validate($event->getSalesChannelContext()->getSalesChannelId());
        } catch (PayPalSettingsInvalidException) {
            return;
        }

        $data = $this->fundingEligibilityDataService->buildData($event->getSalesChannelContext());
        if ($data === null) {
            return;
        }

        $event->getPagelet()->addExtension(self::FUNDING_ELIGIBILITY_EXTENSION, $data);
    }
}
