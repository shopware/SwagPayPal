<?php declare(strict_types=1);
/*
 * (c) shopware AG <info@shopware.com>
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Swag\PayPal\Util\Lifecycle;

use Shopware\Core\Checkout\Payment\PaymentException;
use Shopware\Core\Checkout\Payment\PaymentMethodCollection;
use Shopware\Core\Content\Category\Exception\CategoryNotFoundException;
use Shopware\Core\Framework\Context;
use Shopware\Core\Framework\DataAbstractionLayer\EntityRepository;
use Shopware\Core\Framework\DataAbstractionLayer\Search\Criteria;
use Shopware\Core\Framework\DataAbstractionLayer\Search\Filter\EqualsAnyFilter;
use Shopware\Core\Framework\DataAbstractionLayer\Search\Filter\EqualsFilter;
use Shopware\Core\Framework\Log\Package;
use Shopware\Core\Framework\Plugin\Context\UpdateContext;
use Shopware\Core\System\SalesChannel\SalesChannelCollection;
use Shopware\Core\System\SystemConfig\SystemConfigService;
use Swag\PayPal\Checkout\Payment\Method\PUIHandler;
use Swag\PayPal\Checkout\Payment\PayPalPaymentHandler;
use Swag\PayPal\Pos\Api\Exception\PosApiException;
use Swag\PayPal\Pos\Setting\Exception\CustomerGroupNotFoundException;
use Swag\PayPal\Pos\Setting\Service\InformationDefaultService;
use Swag\PayPal\Pos\Setting\Struct\AdditionalInformation;
use Swag\PayPal\Pos\Util\PosSalesChannelTrait;
use Swag\PayPal\Pos\Webhook\Exception\WebhookNotRegisteredException;
use Swag\PayPal\Pos\Webhook\WebhookService as PosWebhookService;
use Swag\PayPal\RestApi\V1\Api\Payment\ApplicationContext as ApplicationContextV1;
use Swag\PayPal\RestApi\V1\PaymentIntentV1;
use Swag\PayPal\RestApi\V2\Api\Order\ApplicationContext;
use Swag\PayPal\RestApi\V2\Api\Order\ApplicationContext as ApplicationContextV2;
use Swag\PayPal\RestApi\V2\Api\Order\PaymentSource\Common\ExperienceContext;
use Swag\PayPal\RestApi\V2\PaymentIntentV2;
use Swag\PayPal\Setting\Exception\PayPalSettingsInvalidException;
use Swag\PayPal\Setting\Settings;
use Swag\PayPal\SwagPayPal;
use Swag\PayPal\Util\Lifecycle\Installer\PaymentMethodInstaller;
use Swag\PayPal\Util\Lifecycle\Method\ApplePayMethodData;
use Swag\PayPal\Util\Lifecycle\Method\GooglePayMethodData;
use Swag\PayPal\Util\Lifecycle\Method\OxxoMethodData;
use Swag\PayPal\Util\Lifecycle\Method\PayLaterMethodData;
use Swag\PayPal\Util\Lifecycle\Method\PaymentMethodDataRegistry;
use Swag\PayPal\Util\Lifecycle\Method\PUIMethodData;
use Swag\PayPal\Util\Lifecycle\Method\TrustlyMethodData;
use Swag\PayPal\Util\Lifecycle\Method\VenmoMethodData;
use Swag\PayPal\Util\Lifecycle\State\PaymentMethodStateService;
use Swag\PayPal\Webhook\Exception\WebhookIdInvalidException;
use Swag\PayPal\Webhook\WebhookServiceInterface;

/**
 * @internal
 */
#[Package('checkout')]
class Update
{
    use PosSalesChannelTrait;

    public function __construct(
        private readonly SystemConfigService $systemConfig,
        private readonly EntityRepository $paymentRepository,
        private readonly EntityRepository $customFieldRepository,
        private readonly ?WebhookServiceInterface $webhookService,
        private readonly EntityRepository $salesChannelRepository,
        private readonly EntityRepository $salesChannelTypeRepository,
        private readonly ?InformationDefaultService $informationDefaultService,
        private readonly EntityRepository $shippingRepository,
        private readonly ?PosWebhookService $posWebhookService,
        private readonly PaymentMethodInstaller $paymentMethodInstaller,
        private readonly PaymentMethodStateService $paymentMethodStateService,
        private readonly PaymentMethodDataRegistry $paymentMethodDataRegistry,
    ) {
    }

    public function update(UpdateContext $updateContext): void
    {
        if (\version_compare($updateContext->getCurrentPluginVersion(), '1.3.0', '<')) {
            $this->updateTo130();
        }

        if (\version_compare($updateContext->getCurrentPluginVersion(), '1.7.0', '<')) {
            $this->updateTo170();
        }

        if (\version_compare($updateContext->getCurrentPluginVersion(), '1.7.2', '<')) {
            $this->updateTo172($updateContext->getContext());
        }

        if (\version_compare($updateContext->getCurrentPluginVersion(), '2.0.0', '<')) {
            $this->updateTo200($updateContext->getContext());
        }

        if (\version_compare($updateContext->getCurrentPluginVersion(), '3.0.0', '<')) {
            $this->updateTo300($updateContext->getContext());
        }

        if (\version_compare($updateContext->getCurrentPluginVersion(), '5.0.0', '<')) {
            $this->updateTo500($updateContext->getContext());
        }

        if (\version_compare($updateContext->getCurrentPluginVersion(), '5.3.0', '<')) {
            $this->updateTo530($updateContext->getContext());
        }

        if (\version_compare($updateContext->getCurrentPluginVersion(), '5.3.1', '<')) {
            $this->updateTo531($updateContext->getContext());
        }

        if (\version_compare($updateContext->getCurrentPluginVersion(), '5.4.0', '<')) {
            $this->updateTo540($updateContext->getContext());
        }

        if (\version_compare($updateContext->getCurrentPluginVersion(), '5.4.6', '<')) {
            $this->updateTo546($updateContext->getContext());
        }

        if (\version_compare($updateContext->getCurrentPluginVersion(), '6.0.0', '<')) {
            $this->updateTo600($updateContext->getContext());
        }

        if (\version_compare($updateContext->getCurrentPluginVersion(), '6.2.0', '<')) {
            $this->updateTo620();
        }

        if (\version_compare($updateContext->getCurrentPluginVersion(), '7.3.0', '<')) {
            $this->updateTo730();
        }

        if (\version_compare($updateContext->getCurrentPluginVersion(), '9.0.0', '<')) {
            $this->updateTo900($updateContext->getContext());
        }

        if (\version_compare($updateContext->getCurrentPluginVersion(), '9.0.2', '<')) {
            $this->updateTo902($updateContext->getContext());
        }

        if (\version_compare($updateContext->getCurrentPluginVersion(), '9.1.0', '<')) {
            $this->updateTo910($updateContext->getContext());
        }

        if (\version_compare($updateContext->getCurrentPluginVersion(), '9.2.0', '<')) {
            $this->updateTo920($updateContext->getContext());
        }

        if (\version_compare($updateContext->getCurrentPluginVersion(), '9.3.1', '<')) {
            $this->updateTo931($updateContext->getContext());
        }

        if (\version_compare($updateContext->getCurrentPluginVersion(), '9.6.1', '<')) {
            $this->updateTo961($updateContext->getContext());
        }
    }

    private function updateTo130(): void
    {
        if (!$this->systemConfig->get(Settings::SANDBOX)) {
            return;
        }

        $previousClientId = $this->systemConfig->get(Settings::CLIENT_ID);
        $previousClientSecret = $this->systemConfig->get(Settings::CLIENT_SECRET);
        $previousClientIdSandbox = $this->systemConfig->get(Settings::CLIENT_ID_SANDBOX);
        $previousClientSecretSandbox = $this->systemConfig->get(Settings::CLIENT_SECRET_SANDBOX);

        if ($previousClientId && $previousClientSecret
            && $previousClientIdSandbox === null && $previousClientSecretSandbox === null) {
            $this->systemConfig->set(Settings::CLIENT_ID_SANDBOX, $previousClientId);
            $this->systemConfig->set(Settings::CLIENT_SECRET_SANDBOX, $previousClientSecret);
            $this->systemConfig->set(Settings::CLIENT_ID, '');
            $this->systemConfig->set(Settings::CLIENT_SECRET, '');
        }
    }

    private function updateTo170(): void
    {
        if ($this->webhookService === null) {
            // If the WebhookService is `null`, the plugin is deactivated.
            return;
        }

        try {
            $this->webhookService->registerWebhook(null);
        } catch (PayPalSettingsInvalidException $exception) {
            // do nothing, if the plugin is not correctly configured
        }
    }

    private function updateTo172(Context $context): void
    {
        $criteria = new Criteria();
        $criteria->addFilter(new EqualsFilter('name', SwagPayPal::ORDER_TRANSACTION_CUSTOM_FIELDS_PAYPAL_TRANSACTION_ID));

        $customFieldIds = $this->customFieldRepository->searchIds($criteria, $context);

        if ($customFieldIds->getTotal() === 0) {
            return;
        }

        $data = \array_map(static function ($id) {
            return ['id' => $id];
        }, $customFieldIds->getIds());
        $this->customFieldRepository->delete($data, $context);
    }

    private function updateTo200(Context $context): void
    {
        $this->changePaymentHandlerIdentifier(
            'Swag\PayPal\Payment\PayPalPaymentHandler',
            PayPalPaymentHandler::class,
            $context
        );
        $this->changePaymentHandlerIdentifier(
            'Swag\PayPal\Payment\PayPalPuiPaymentHandler',
            'Swag\PayPal\Checkout\Payment\PayPalPuiPaymentHandler',
            $context
        );
        $this->migrateIntentSetting($context);
        $this->migrateLandingPageSetting($context);
    }

    private function updateTo300(Context $context): void
    {
        if ($this->informationDefaultService === null) {
            // Plugin is not activated, no entities created
            return;
        }

        $this->salesChannelTypeRepository->upsert([
            [
                'id' => SwagPayPal::SALES_CHANNEL_TYPE_POS,
                'name' => 'Point of Sale – Zettle by PayPal',
                'descriptionLong' => 'Zettle’s point-of-sale system allows you to accept cash, card or contactless payments. Connect Shopware to Zettle to keep products, stocks and sales in sync – all in one place.',
                'translations' => [
                    'en-GB' => [
                        'name' => 'Point of Sale – Zettle by PayPal',
                        'descriptionLong' => 'Zettle’s point-of-sale system allows you to accept cash, card or contactless payments. Connect Shopware to Zettle to keep products, stocks and sales in sync – all in one place.',
                    ],
                    'de-DE' => [
                        'name' => 'Point of Sale – Zettle by PayPal',
                        'descriptionLong' => 'Mit Zettles Point-of-Sale-Lösung kannst Du Zahlungen in bar, mit Karte oder kontaktlos entgegennehmen. Verbinde Shopware mit Zettle, um Produkte, Lagerbestände und Verkäufe synchron zu halten - Alles an einem Ort.',
                    ],
                ],
            ],
        ], $context);

        try {
            $this->informationDefaultService->addInformation(new AdditionalInformation(), $context);
        } catch (CustomerGroupNotFoundException|CategoryNotFoundException $e) {
            // ignore, we only need payment and shipping method
        }

        $this->paymentRepository->upsert([[
            'id' => InformationDefaultService::POS_PAYMENT_METHOD_ID,
            'name' => 'Zettle by PayPal',
            'description' => 'Payment via Zettle by PayPal. Do not activate or use.',
            'translations' => [
                'de-DE' => [
                    'description' => 'Bezahlung per Zettle by PayPal. Nicht aktivieren oder nutzen.',
                ],
                'en-GB' => [
                    'description' => 'Payment via Zettle by PayPal. Do not activate or use.',
                ],
            ],
        ]], $context);

        $this->shippingRepository->upsert([[
            'id' => InformationDefaultService::POS_SHIPPING_METHOD_ID,
            'active' => false,
            'name' => 'Zettle by PayPal',
            'description' => 'Shipping via Zettle by PayPal. Do not activate or use.',
            'translations' => [
                'de-DE' => [
                    'description' => 'Versand per Zettle by PayPal. Nicht aktivieren oder nutzen.',
                ],
                'en-GB' => [
                    'description' => 'Shipping via Zettle by PayPal. Do not activate or use.',
                ],
            ],
        ]], $context);

        if ($this->webhookService === null || $this->posWebhookService === null) {
            // If the WebhookService is `null`, the plugin is deactivated.
            return;
        }

        $salesChannelIds = $this->salesChannelRepository->searchIds(new Criteria(), $context)->getIds();
        $salesChannelIds[] = null;

        try {
            foreach ($salesChannelIds as $salesChannelId) {
                if (!\is_string($salesChannelId) && $salesChannelId !== null) {
                    continue;
                }

                if (!$this->systemConfig->get(Settings::WEBHOOK_ID, $salesChannelId)) {
                    continue;
                }

                $this->webhookService->deregisterWebhook($salesChannelId);
                $this->webhookService->registerWebhook($salesChannelId);
            }
        } catch (PayPalSettingsInvalidException|WebhookIdInvalidException $exception) {
            // do nothing, if the plugin is not correctly configured
        }

        $criteria = new Criteria();
        $criteria->addFilter(new EqualsFilter('typeId', SwagPayPal::SALES_CHANNEL_TYPE_POS));
        $criteria->addAssociation(SwagPayPal::SALES_CHANNEL_POS_EXTENSION);

        /** @var SalesChannelCollection $posSalesChannels */
        $posSalesChannels = $this->salesChannelRepository->search($criteria, $context)->getEntities();

        try {
            foreach ($posSalesChannels as $salesChannel) {
                if (!$this->getPosSalesChannel($salesChannel)->getWebhookSigningKey()) {
                    continue;
                }

                $this->posWebhookService->registerWebhook($salesChannel->getId(), $context);
            }
        } catch (PosApiException|WebhookNotRegisteredException $exception) {
            // do nothing, if the Sales Channel is not correctly configured
        }
    }

    private function updateTo500(Context $context): void
    {
        $this->changePaymentHandlerIdentifier('Swag\PayPal\Checkout\Payment\PayPalPuiPaymentHandler', PUIHandler::class, $context);
        $this->paymentMethodStateService->setPaymentMethodState(PUIMethodData::class, false, $context);
        $this->paymentMethodInstaller->installAll($context);
        $this->setSettingToDefaultValue(Settings::PUI_CUSTOMER_SERVICE_INSTRUCTIONS);
    }

    private function updateTo530(Context $context): void
    {
        $this->paymentMethodInstaller->install(VenmoMethodData::class, $context);
        $this->paymentMethodInstaller->install(PayLaterMethodData::class, $context);
    }

    private function updateTo531(Context $context): void
    {
        $this->paymentMethodInstaller->install(OxxoMethodData::class, $context);
        $this->paymentMethodInstaller->install(PayLaterMethodData::class, $context);
    }

    private function updateTo540(Context $context): void
    {
        $this->paymentMethodInstaller->removeRules($context);
        $this->setSettingToDefaultValue(Settings::ACDC_FORCE_3DS);
    }

    private function updateTo546(Context $context): void
    {
        // can be removed, when Trustly is available again
        $this->paymentMethodStateService->setPaymentMethodState(TrustlyMethodData::class, false, $context);
    }

    private function changePaymentHandlerIdentifier(string $previousHandler, string $newHandler, Context $context): void
    {
        $criteria = new Criteria();
        $criteria->addFilter(new EqualsFilter('handlerIdentifier', $previousHandler));

        $paymentMethodId = $this->paymentRepository->searchIds($criteria, $context)->firstId();
        if ($paymentMethodId === null) {
            return;
        }

        $this->paymentRepository->update([[
            'id' => $paymentMethodId,
            'handlerIdentifier' => $newHandler,
        ]], $context);
    }

    private function migrateIntentSetting(Context $context): void
    {
        $salesChannelIds = $this->getSalesChannelIds($context);

        foreach ($salesChannelIds as $salesChannelId) {
            $intent = $this->systemConfig->getString(Settings::INTENT, $salesChannelId);

            if ($salesChannelId !== null && $intent === $this->systemConfig->getString(Settings::INTENT)) {
                continue;
            }

            if ($intent === '') {
                continue;
            }

            if (\in_array($intent, PaymentIntentV2::INTENTS, true)) {
                continue;
            }

            if (!\in_array($intent, PaymentIntentV1::INTENTS, true)) {
                throw new \RuntimeException('Invalid value for "' . Settings::INTENT . '" setting');
            }

            if ($intent === PaymentIntentV1::SALE) {
                $this->systemConfig->set(Settings::INTENT, PaymentIntentV2::CAPTURE, $salesChannelId);

                continue;
            }

            $this->systemConfig->set(Settings::INTENT, PaymentIntentV2::AUTHORIZE, $salesChannelId);
        }
    }

    private function migrateLandingPageSetting(Context $context): void
    {
        $salesChannelIds = $this->getSalesChannelIds($context);

        foreach ($salesChannelIds as $salesChannelId) {
            $landingPage = $this->systemConfig->getString(Settings::LANDING_PAGE, $salesChannelId);

            if ($salesChannelId !== null && $landingPage === $this->systemConfig->getString(Settings::LANDING_PAGE)) {
                continue;
            }

            if ($landingPage === '') {
                continue;
            }

            if (\in_array($landingPage, ApplicationContext::LANDING_PAGE_TYPES, true)) {
                continue;
            }

            if (!\in_array($landingPage, ApplicationContextV1::LANDING_PAGE_TYPES, true)) {
                throw new \RuntimeException('Invalid value for "' . Settings::LANDING_PAGE . '" setting');
            }

            if ($landingPage === ApplicationContextV1::LANDING_PAGE_TYPE_LOGIN) {
                $this->systemConfig->set(Settings::LANDING_PAGE, ApplicationContextV2::LANDING_PAGE_TYPE_LOGIN, $salesChannelId);

                continue;
            }

            $this->systemConfig->set(Settings::LANDING_PAGE, ApplicationContextV2::LANDING_PAGE_TYPE_BILLING, $salesChannelId);
        }
    }

    private function getSalesChannelIds(Context $context): array
    {
        $salesChannelIds = $this->salesChannelRepository->searchIds(new Criteria(), $context)->getIds();
        $salesChannelIds[] = null; // Global config for all sales channels

        return $salesChannelIds;
    }

    private function setSettingToDefaultValue(string $setting, ?string $overrideValue = null, ?string $salesChannelId = null): void
    {
        $value = Settings::DEFAULT_VALUES[$setting] ?? null;
        $this->systemConfig->set($setting, $overrideValue ?? $value, $salesChannelId);
    }

    private function updateTo600(Context $context): void
    {
        if ($this->posWebhookService === null) {
            // plugin not active, type not installed
            return;
        }

        $this->salesChannelTypeRepository->update([
            [
                'id' => SwagPayPal::SALES_CHANNEL_TYPE_POS,
                'iconName' => 'regular-money-bill',
            ],
        ], $context);
    }

    private function updateTo620(): void
    {
        $this->setSettingToDefaultValue(Settings::ECS_SHOW_PAY_LATER);
    }

    private function updateTo730(): void
    {
        $installmentBannerEnabled = $this->systemConfig->getBool(Settings::SYSTEM_CONFIG_DOMAIN . 'installmentBannerEnabled');

        $this->systemConfig->set(Settings::INSTALLMENT_BANNER_DETAIL_PAGE_ENABLED, $installmentBannerEnabled);
        $this->systemConfig->set(Settings::INSTALLMENT_BANNER_CART_ENABLED, $installmentBannerEnabled);
        $this->systemConfig->set(Settings::INSTALLMENT_BANNER_OFF_CANVAS_CART_ENABLED, $installmentBannerEnabled);
        $this->systemConfig->set(Settings::INSTALLMENT_BANNER_LOGIN_PAGE_ENABLED, $installmentBannerEnabled);
        $this->systemConfig->set(Settings::INSTALLMENT_BANNER_FOOTER_ENABLED, $installmentBannerEnabled);
    }

    private function updateTo900(Context $context): void
    {
        $criteria = (new Criteria())
            ->addFilter(new EqualsAnyFilter(
                'handlerIdentifier',
                $this->paymentMethodDataRegistry->getPaymentHandlers()
            ));

        /** @var PaymentMethodCollection $paymentMethods */
        $paymentMethods = $this->paymentRepository->search($criteria, $context)->getEntities();

        $upsertData = [];
        foreach ($paymentMethods as $method) {
            $handler = $this->paymentMethodDataRegistry->getPaymentMethodByHandler($method->getHandlerIdentifier());
            if ($handler === null) {
                continue;
            }

            $upsertData[] = [
                'id' => $method->getId(),
                'technicalName' => $handler->getTechnicalName(),
            ];
        }

        $this->paymentRepository->upsert($upsertData, $context);
    }

    private function updateTo902(Context $context): void
    {
        $salesChannelIds = $this->getSalesChannelIds($context);

        foreach ($salesChannelIds as $salesChannelId) {
            $landingPage = $this->systemConfig->getString(Settings::LANDING_PAGE, $salesChannelId);

            if ($landingPage === ApplicationContextV2::LANDING_PAGE_TYPE_BILLING) {
                $this->systemConfig->set(Settings::LANDING_PAGE, ExperienceContext::LANDING_PAGE_TYPE_GUEST, $salesChannelId);
            }
        }
    }

    private function updateTo910(Context $context): void
    {
        $this->paymentMethodInstaller->install(ApplePayMethodData::class, $context);
        $this->paymentMethodInstaller->install(GooglePayMethodData::class, $context);
    }

    private function updateTo920(Context $context): void
    {
        try {
            $this->paymentMethodStateService->setPaymentMethodStateByHandler('Swag\PayPal\Checkout\Payment\Method\SofortAPMHandler', false, $context);
        } catch (PaymentException) {
        }
    }

    private function updateTo931(Context $context): void
    {
        try {
            $this->paymentMethodStateService->setPaymentMethodStateByHandler('Swag\PayPal\Checkout\Payment\Method\GiropayAPMHandler', false, $context);
        } catch (PaymentException) {
        }
    }

    private function updateTo961(Context $context): void
    {
        try {
            $this->paymentMethodStateService->setPaymentMethodState(TrustlyMethodData::class, false, $context);
        } catch (PaymentException) {
        }

        $criteria = (new Criteria([InformationDefaultService::POS_PAYMENT_METHOD_ID]));
        $paymentMethodId = $this->paymentRepository->searchIds($criteria, $context)->firstId();
        if ($paymentMethodId !== null) {
            $this->paymentRepository->upsert([[
                'id' => $paymentMethodId,
                'technicalName' => 'swag_paypal_pos',
            ]], $context);
        }

        $criteria = (new Criteria([InformationDefaultService::POS_SHIPPING_METHOD_ID]));
        $shippingMethodId = $this->shippingRepository->searchIds($criteria, $context)->firstId();
        if ($shippingMethodId !== null) {
            $this->shippingRepository->upsert([[
                'id' => $shippingMethodId,
                'technicalName' => 'swag_paypal_pos',
            ]], $context);
        }
    }
}
