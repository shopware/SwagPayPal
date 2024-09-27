<?php declare(strict_types=1);
/*
 * (c) shopware AG <info@shopware.com>
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Swag\PayPal\Storefront\Data\Service;

use Shopware\Core\Checkout\Order\OrderEntity;
use Shopware\Core\Framework\Log\Package;
use Shopware\Core\System\SalesChannel\SalesChannelContext;
use Shopware\Core\System\SystemConfig\SystemConfigService;
use Swag\PayPal\RestApi\PartnerAttributionId;
use Swag\PayPal\Setting\Service\CredentialsUtilInterface;
use Swag\PayPal\Setting\Settings;
use Swag\PayPal\Util\LocaleCodeProvider;

#[Package('checkout')]
abstract class AbstractScriptDataService
{
    /**
     * @internal
     */
    public function __construct(
        protected readonly LocaleCodeProvider $localeCodeProvider,
        protected readonly SystemConfigService $systemConfigService,
        protected readonly CredentialsUtilInterface $credentialsUtil,
    ) {
    }

    protected function getBaseData(SalesChannelContext $context, ?OrderEntity $order = null): array
    {
        $salesChannelId = $context->getSalesChannelId();
        $merchantPayerId = $this->credentialsUtil->getMerchantPayerId($salesChannelId);

        return [
            'clientId' => $this->credentialsUtil->getClientId($salesChannelId),
            'merchantPayerId' => $merchantPayerId,
            'languageIso' => $this->getButtonLanguage($context),
            'currency' => $context->getCurrency()->getIsoCode(),
            'intent' => \mb_strtolower($this->systemConfigService->getString(Settings::INTENT, $salesChannelId)),
            'partnerAttributionId' => $merchantPayerId ? PartnerAttributionId::PAYPAL_PPCP : PartnerAttributionId::PAYPAL_CLASSIC,
        ];
    }

    protected function getButtonLanguage(SalesChannelContext $context): string
    {
        if ($settingsLocale = $this->systemConfigService->getString(Settings::SPB_BUTTON_LANGUAGE_ISO, $context->getSalesChannelId())) {
            return $this->localeCodeProvider->getFormattedLocaleCode($settingsLocale);
        }

        return $this->localeCodeProvider->getFormattedLocaleCode(
            $this->localeCodeProvider->getLocaleCodeFromContext($context->getContext())
        );
    }
}
