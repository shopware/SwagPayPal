<?php declare(strict_types=1);
/*
 * (c) shopware AG <info@shopware.com>
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Swag\PayPal\Storefront\Data\Service;

use Shopware\Core\Framework\Log\Package;
use Shopware\Core\System\SalesChannel\SalesChannelContext;
use Shopware\Core\System\SystemConfig\SystemConfigService;
use Swag\PayPal\Checkout\SalesChannel\MethodEligibilityRoute;
use Swag\PayPal\Setting\Service\CredentialsUtilInterface;
use Swag\PayPal\Setting\Settings;
use Swag\PayPal\Storefront\Data\Struct\FundingEligibilityData;
use Swag\PayPal\Util\LocaleCodeProvider;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\Routing\RouterInterface;

#[Package('checkout')]
class FundingEligibilityDataService
{
    private CredentialsUtilInterface $credentialsUtil;

    private SystemConfigService $systemConfigService;

    private LocaleCodeProvider $localeCodeProvider;

    private RouterInterface $router;

    private RequestStack $requestStack;

    /**
     * @internal
     */
    public function __construct(
        CredentialsUtilInterface $credentialsUtil,
        SystemConfigService $systemConfigService,
        LocaleCodeProvider $localeCodeProvider,
        RouterInterface $router,
        RequestStack $requestStack
    ) {
        $this->credentialsUtil = $credentialsUtil;
        $this->systemConfigService = $systemConfigService;
        $this->localeCodeProvider = $localeCodeProvider;
        $this->router = $router;
        $this->requestStack = $requestStack;
    }

    public function buildData(SalesChannelContext $context): ?FundingEligibilityData
    {
        return (new FundingEligibilityData())->assign(
            [
                'clientId' => $this->credentialsUtil->getClientId($context->getSalesChannelId()),
                'merchantPayerId' => $this->credentialsUtil->getMerchantPayerId($context->getSalesChannelId()),
                'languageIso' => $this->getButtonLanguage($context),
                'currency' => $context->getCurrency()->getIsoCode(),
                'intent' => \mb_strtolower($this->systemConfigService->getString(Settings::INTENT, $context->getSalesChannelId())),
                'methodEligibilityUrl' => $this->router->generate('frontend.paypal.payment-method-eligibility'),
                'filteredPaymentMethods' => $this->getFilteredPaymentMethods(),
            ]
        );
    }

    private function getButtonLanguage(SalesChannelContext $context): string
    {
        if ($settingsLocale = $this->systemConfigService->getString(Settings::SPB_BUTTON_LANGUAGE_ISO, $context->getSalesChannelId())) {
            return $this->localeCodeProvider->getFormattedLocaleCode($settingsLocale);
        }

        return $this->localeCodeProvider->getFormattedLocaleCode(
            $this->localeCodeProvider->getLocaleCodeFromContext($context->getContext())
        );
    }

    private function getFilteredPaymentMethods(): array
    {
        $handlers = $this->requestStack->getSession()->get(MethodEligibilityRoute::SESSION_KEY, []);
        if (!$handlers) {
            return [];
        }

        $methods = [];
        foreach ($handlers as $handler) {
            $methods[] = \array_search($handler, MethodEligibilityRoute::REMOVABLE_PAYMENT_HANDLERS, true);
        }

        return \array_filter($methods);
    }
}
