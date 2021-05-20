<?php declare(strict_types=1);
/*
 * (c) shopware AG <info@shopware.com>
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Swag\PayPal\PaymentsApi\Builder;

use Psr\Log\LoggerInterface;
use Shopware\Core\System\SalesChannel\SalesChannelContext;
use Shopware\Core\System\SystemConfig\SystemConfigService;
use Swag\PayPal\RestApi\V1\Api\Payment\ApplicationContext;
use Swag\PayPal\RestApi\V1\Api\Payment\Payer;
use Swag\PayPal\RestApi\V1\Api\Payment\RedirectUrls;
use Swag\PayPal\Setting\Service\SettingsServiceInterface;
use Swag\PayPal\Setting\Settings;
use Swag\PayPal\Setting\SwagPayPalSettingStruct;
use Swag\PayPal\Util\LocaleCodeProvider;
use Swag\PayPal\Util\PriceFormatter;
use Symfony\Contracts\EventDispatcher\EventDispatcherInterface;

abstract class AbstractPaymentBuilder
{
    /**
     * @deprecated tag:v4.0.0 - will be removed
     *
     * @var SettingsServiceInterface
     */
    protected $settingsService;

    /**
     * @deprecated tag:v4.0.0 - will be removed
     *
     * @var SwagPayPalSettingStruct|null
     */
    protected $settings;

    /**
     * @var LocaleCodeProvider
     */
    protected $localeCodeProvider;

    /**
     * @var PriceFormatter
     */
    protected $priceFormatter;

    /**
     * @var EventDispatcherInterface
     */
    protected $eventDispatcher;

    /**
     * @var LoggerInterface
     */
    protected $logger;

    protected ?SystemConfigService $systemConfigService;

    /**
     * @deprecated tag:v4.0.0 - parameter $settingsService will be removed, parameter $systemConfigService will not be nullable
     */
    public function __construct(
        SettingsServiceInterface $settingsService,
        LocaleCodeProvider $localeCodeProvider,
        PriceFormatter $priceFormatter,
        EventDispatcherInterface $eventDispatcher,
        LoggerInterface $logger,
        ?SystemConfigService $systemConfigService = null
    ) {
        $this->settingsService = $settingsService;
        $this->localeCodeProvider = $localeCodeProvider;
        $this->priceFormatter = $priceFormatter;
        $this->eventDispatcher = $eventDispatcher;
        $this->logger = $logger;
        $this->systemConfigService = $systemConfigService;
    }

    protected function createPayer(): Payer
    {
        $payer = new Payer();
        $payer->setPaymentMethod('paypal');

        return $payer;
    }

    protected function createRedirectUrls(string $returnUrl): RedirectUrls
    {
        $redirectUrls = new RedirectUrls();
        $redirectUrls->setCancelUrl(\sprintf('%s&cancel=1', $returnUrl));
        $redirectUrls->setReturnUrl($returnUrl);

        return $redirectUrls;
    }

    protected function getApplicationContext(SalesChannelContext $salesChannelContext): ApplicationContext
    {
        $applicationContext = new ApplicationContext();
        $applicationContext->setLocale(
            $this->localeCodeProvider->getLocaleCodeFromContext(
                $salesChannelContext->getContext()
            )
        );
        $applicationContext->setBrandName($this->getBrandName($salesChannelContext));
        $applicationContext->setLandingPage($this->getLandingPageType($salesChannelContext));

        return $applicationContext;
    }

    private function getBrandName(SalesChannelContext $salesChannelContext): string
    {
        $brandName = null;
        if ($this->settings !== null) {
            $brandName = $this->settings->getBrandName();
        }

        if ($this->systemConfigService !== null) {
            $brandName = $this->systemConfigService->getString(Settings::BRAND_NAME, $salesChannelContext->getSalesChannelId());
        }

        if ($brandName === null || $brandName === '') {
            $brandName = $salesChannelContext->getSalesChannel()->getName() ?? '';
        }

        return $brandName;
    }

    private function getLandingPageType(SalesChannelContext $salesChannelContext): string
    {
        $landingPageType = ApplicationContext::LANDING_PAGE_TYPE_LOGIN;
        if ($this->settings !== null) {
            $landingPageType = $this->settings->getLandingPage();
        }

        if ($this->systemConfigService !== null) {
            $landingPageType = $this->systemConfigService->getString(Settings::LANDING_PAGE, $salesChannelContext->getSalesChannelId());
        }

        if ($landingPageType !== ApplicationContext::LANDING_PAGE_TYPE_BILLING) {
            $landingPageType = ApplicationContext::LANDING_PAGE_TYPE_LOGIN;
        }

        return $landingPageType;
    }
}
