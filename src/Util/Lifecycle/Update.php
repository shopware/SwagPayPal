<?php declare(strict_types=1);
/*
 * (c) shopware AG <info@shopware.com>
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Swag\PayPal\Util\Lifecycle;

use Shopware\Core\Framework\Context;
use Shopware\Core\Framework\DataAbstractionLayer\EntityRepositoryInterface;
use Shopware\Core\Framework\DataAbstractionLayer\Search\Criteria;
use Shopware\Core\Framework\DataAbstractionLayer\Search\Filter\EqualsFilter;
use Shopware\Core\Framework\Plugin\Context\UpdateContext;
use Shopware\Core\System\SystemConfig\SystemConfigService;
use Swag\PayPal\Checkout\Payment\Method\PUIHandler;
use Swag\PayPal\Checkout\Payment\PayPalPaymentHandler;
use Swag\PayPal\Pos\Api\Exception\PosApiException;
use Swag\PayPal\Pos\Setting\Service\InformationDefaultService;
use Swag\PayPal\Pos\Setting\Struct\AdditionalInformation;
use Swag\PayPal\Pos\Util\PosSalesChannelTrait;
use Swag\PayPal\Pos\Webhook\Exception\WebhookNotRegisteredException;
use Swag\PayPal\Pos\Webhook\WebhookService as PosWebhookService;
use Swag\PayPal\RestApi\V1\Api\Payment\ApplicationContext as ApplicationContextV1;
use Swag\PayPal\RestApi\V1\PaymentIntentV1;
use Swag\PayPal\RestApi\V2\Api\Order\ApplicationContext;
use Swag\PayPal\RestApi\V2\Api\Order\ApplicationContext as ApplicationContextV2;
use Swag\PayPal\RestApi\V2\PaymentIntentV2;
use Swag\PayPal\Setting\Exception\PayPalSettingsInvalidException;
use Swag\PayPal\Setting\Settings;
use Swag\PayPal\SwagPayPal;
use Swag\PayPal\Util\Lifecycle\Installer\PaymentMethodInstaller;
use Swag\PayPal\Util\Lifecycle\Method\PUIMethodData;
use Swag\PayPal\Util\Lifecycle\State\PaymentMethodStateService;
use Swag\PayPal\Webhook\Exception\WebhookIdInvalidException;
use Swag\PayPal\Webhook\WebhookService;
use Swag\PayPal\Webhook\WebhookServiceInterface;

class Update
{
    use PosSalesChannelTrait;

    private SystemConfigService $systemConfig;

    private EntityRepositoryInterface $customFieldRepository;

    private ?WebhookServiceInterface $webhookService;

    private EntityRepositoryInterface $paymentRepository;

    private EntityRepositoryInterface $salesChannelRepository;

    private EntityRepositoryInterface $salesChannelTypeRepository;

    private ?InformationDefaultService $informationDefaultService;

    private EntityRepositoryInterface $shippingRepository;

    private ?PosWebhookService $posWebhookService;

    private PaymentMethodInstaller $paymentMethodInstaller;

    private PaymentMethodStateService $paymentMethodStateService;

    public function __construct(
        SystemConfigService $systemConfig,
        EntityRepositoryInterface $paymentRepository,
        EntityRepositoryInterface $customFieldRepository,
        ?WebhookServiceInterface $webhookService,
        EntityRepositoryInterface $salesChannelRepository,
        EntityRepositoryInterface $salesChannelTypeRepository,
        ?InformationDefaultService $informationDefaultService,
        EntityRepositoryInterface $shippingRepository,
        ?PosWebhookService $posWebhookService,
        PaymentMethodInstaller $paymentMethodInstaller,
        PaymentMethodStateService $paymentMethodStateService
    ) {
        $this->systemConfig = $systemConfig;
        $this->customFieldRepository = $customFieldRepository;
        $this->webhookService = $webhookService;
        $this->paymentRepository = $paymentRepository;
        $this->salesChannelRepository = $salesChannelRepository;
        $this->salesChannelTypeRepository = $salesChannelTypeRepository;
        $this->informationDefaultService = $informationDefaultService;
        $this->shippingRepository = $shippingRepository;
        $this->posWebhookService = $posWebhookService;
        $this->paymentMethodInstaller = $paymentMethodInstaller;
        $this->paymentMethodStateService = $paymentMethodStateService;
    }

    public function update(UpdateContext $updateContext): void
    {
        if (\version_compare($updateContext->getCurrentPluginVersion(), '1.1.0', '<')) {
            $this->updateTo110();
        }

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

        if (\version_compare($updateContext->getCurrentPluginVersion(), '4.1.0', '<')) {
            $this->updateTo410();
        }

        if (\version_compare($updateContext->getCurrentPluginVersion(), '4.9.0', '<')) {
            $this->updateTo500($updateContext->getContext());
        }
    }

    private function updateTo110(): void
    {
        $this->systemConfig->set(Settings::INSTALLMENT_BANNER_ENABLED, true);
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

        $this->informationDefaultService->addInformation(new AdditionalInformation(), $context);

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
        } catch (PayPalSettingsInvalidException | WebhookIdInvalidException $exception) {
            // do nothing, if the plugin is not correctly configured
        }

        $criteria = new Criteria();
        $criteria->addFilter(new EqualsFilter('typeId', SwagPayPal::SALES_CHANNEL_TYPE_POS));
        $criteria->addAssociation(SwagPayPal::SALES_CHANNEL_POS_EXTENSION);
        $posSalesChannels = $this->salesChannelRepository->search($criteria, $context)->getEntities();

        try {
            foreach ($posSalesChannels as $salesChannel) {
                if (!$this->getPosSalesChannel($salesChannel)->getWebhookSigningKey()) {
                    continue;
                }

                $this->posWebhookService->registerWebhook($salesChannel->getId(), $context);
            }
        } catch (PosApiException | WebhookNotRegisteredException $exception) {
            // do nothing, if the Sales Channel is not correctly configured
        }
    }

    private function updateTo410(): void
    {
        $this->systemConfig->set(Settings::SPB_SHOW_PAY_LATER, true);
    }

    private function updateTo500(Context $context): void
    {
        $this->changePaymentHandlerIdentifier('Swag\PayPal\Checkout\Payment\PayPalPuiPaymentHandler', PUIHandler::class, $context);
        $this->paymentMethodStateService->setPaymentMethodState(PUIMethodData::class, false, $context);
        $this->paymentMethodInstaller->installAll($context);
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
}
