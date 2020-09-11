<?php declare(strict_types=1);
/*
 * (c) shopware AG <info@shopware.com>
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Swag\PayPal\PaymentsApi\Builder;

use Shopware\Core\System\SalesChannel\SalesChannelContext;
use Swag\PayPal\RestApi\V1\Api\Payment\ApplicationContext;
use Swag\PayPal\RestApi\V1\Api\Payment\Payer;
use Swag\PayPal\RestApi\V1\Api\Payment\RedirectUrls;
use Swag\PayPal\RestApi\V1\PaymentIntentV1;
use Swag\PayPal\Setting\Exception\PayPalSettingsInvalidException;
use Swag\PayPal\Setting\Service\SettingsServiceInterface;
use Swag\PayPal\Setting\SwagPayPalSettingStruct;
use Swag\PayPal\Util\LocaleCodeProvider;
use Swag\PayPal\Util\PriceFormatter;

abstract class AbstractPaymentBuilder
{
    /**
     * @var SettingsServiceInterface
     */
    protected $settingsService;

    /**
     * @var SwagPayPalSettingStruct
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

    public function __construct(
        SettingsServiceInterface $settingsService,
        LocaleCodeProvider $localeCodeProvider,
        PriceFormatter $priceFormatter
    ) {
        $this->settingsService = $settingsService;
        $this->localeCodeProvider = $localeCodeProvider;
        $this->priceFormatter = $priceFormatter;
    }

    protected function getIntent(): string
    {
        $intent = $this->settings->getIntent();
        $this->validateIntent($intent);

        return $intent;
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
        $applicationContext->setLandingPage($this->getLandingPageType());

        return $applicationContext;
    }

    /**
     * @throws PayPalSettingsInvalidException
     */
    private function validateIntent(string $intent): void
    {
        if (!\in_array($intent, PaymentIntentV1::INTENTS, true)) {
            throw new PayPalSettingsInvalidException('intent');
        }
    }

    private function getBrandName(SalesChannelContext $salesChannelContext): string
    {
        $brandName = $this->settings->getBrandName();

        if ($brandName === null || $brandName === '') {
            $brandName = $salesChannelContext->getSalesChannel()->getName() ?? '';
        }

        return $brandName;
    }

    private function getLandingPageType(): string
    {
        $landingPageType = $this->settings->getLandingPage();
        if ($landingPageType !== ApplicationContext::LANDING_PAGE_TYPE_BILLING) {
            $landingPageType = ApplicationContext::LANDING_PAGE_TYPE_LOGIN;
        }

        return $landingPageType;
    }
}
