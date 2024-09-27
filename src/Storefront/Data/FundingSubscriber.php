<?php declare(strict_types=1);
/*
 * (c) shopware AG <info@shopware.com>
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Swag\PayPal\Storefront\Data;

use Shopware\Core\Framework\Log\Package;
use Shopware\Storefront\Pagelet\Footer\FooterPageletLoadedEvent;
use Swag\PayPal\Setting\Exception\PayPalSettingsInvalidException;
use Swag\PayPal\Setting\Service\SettingsValidationServiceInterface;
use Swag\PayPal\Storefront\Data\Service\FundingEligibilityDataService;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;

/**
 * @internal
 */
#[Package('checkout')]
class FundingSubscriber implements EventSubscriberInterface
{
    public const FUNDING_ELIGIBILITY_EXTENSION = 'swagPayPalFundingEligibility';

    private FundingEligibilityDataService $fundingEligibilityDataService;

    private SettingsValidationServiceInterface $settingsValidationService;

    public function __construct(
        SettingsValidationServiceInterface $settingsValidationService,
        FundingEligibilityDataService $fundingEligibilityDataService,
    ) {
        $this->settingsValidationService = $settingsValidationService;
        $this->fundingEligibilityDataService = $fundingEligibilityDataService;
    }

    public static function getSubscribedEvents(): array
    {
        return [
            FooterPageletLoadedEvent::class => 'addFundingAvailabilityData',
        ];
    }

    public function addFundingAvailabilityData(FooterPageletLoadedEvent $event): void
    {
        try {
            $this->settingsValidationService->validate($event->getSalesChannelContext()->getSalesChannelId());
        } catch (PayPalSettingsInvalidException $e) {
            return;
        }

        $data = $this->fundingEligibilityDataService->buildData($event->getSalesChannelContext());
        if ($data === null) {
            return;
        }

        $event->getPagelet()->addExtension(self::FUNDING_ELIGIBILITY_EXTENSION, $data);
    }
}
