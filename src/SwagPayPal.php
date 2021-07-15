<?php declare(strict_types=1);
/*
 * (c) shopware AG <info@shopware.com>
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Swag\PayPal;

use Doctrine\DBAL\Connection;
use Shopware\Core\Framework\DataAbstractionLayer\EntityRepositoryInterface;
use Shopware\Core\Framework\Plugin;
use Shopware\Core\Framework\Plugin\Context\ActivateContext;
use Shopware\Core\Framework\Plugin\Context\DeactivateContext;
use Shopware\Core\Framework\Plugin\Context\InstallContext;
use Shopware\Core\Framework\Plugin\Context\UninstallContext;
use Shopware\Core\Framework\Plugin\Context\UpdateContext;
use Shopware\Core\Framework\Plugin\Util\PluginIdProvider;
use Shopware\Core\System\CustomField\CustomFieldDefinition;
use Shopware\Core\System\SystemConfig\SystemConfigService;
use Swag\PayPal\Pos\Setting\Service\InformationDefaultService;
use Swag\PayPal\Pos\Webhook\WebhookService as PosWebhookService;
use Swag\PayPal\Util\Lifecycle\ActivateDeactivate;
use Swag\PayPal\Util\Lifecycle\InstallUninstall;
use Swag\PayPal\Util\Lifecycle\Update;
use Swag\PayPal\Webhook\WebhookService;
use Symfony\Component\DependencyInjection\ContainerInterface;

class SwagPayPal extends Plugin
{
    public const ORDER_TRANSACTION_CUSTOM_FIELDS_PAYPAL_TRANSACTION_ID = 'swag_paypal_transaction_id';
    public const ORDER_TRANSACTION_CUSTOM_FIELDS_PAYPAL_TOKEN = 'swag_paypal_token';
    public const ORDER_TRANSACTION_CUSTOM_FIELDS_PAYPAL_PUI_INSTRUCTION = 'swag_paypal_pui_payment_instruction';
    public const ORDER_TRANSACTION_CUSTOM_FIELDS_PAYPAL_ORDER_ID = 'swag_paypal_order_id';
    public const ORDER_TRANSACTION_CUSTOM_FIELDS_PAYPAL_PARTNER_ATTRIBUTION_ID = 'swag_paypal_partner_attribution_id';
    public const ORDER_TRANSACTION_CUSTOM_FIELDS_PAYPAL_RESOURCE_ID = 'swag_paypal_resource_id';
    public const SALES_CHANNEL_TYPE_POS = '1ce0868f406d47d98cfe4b281e62f099';
    public const SALES_CHANNEL_POS_EXTENSION = 'paypalPosSalesChannel';
    public const PRODUCT_LOG_POS_EXTENSION = 'paypalPosLog';
    public const PRODUCT_SYNC_POS_EXTENSION = 'paypalPosSync';
    public const POS_PARTNER_CLIENT_ID = '48804990-9c6d-4579-9c39-eae6d93e5f94';
    public const POS_PARTNER_IDENTIFIER = 'shopware';

    private const PAYPAL_POS_SALES_CHANNEL_PRIVILEGE_READ = 'swag_paypal_pos_sales_channel:read';
    private const PAYPAL_POS_SALES_CHANNEL_PRIVILEGE_UPDATE = 'swag_paypal_pos_sales_channel:update';
    private const PAYPAL_POS_SALES_CHANNEL_PRIVILEGE_CREATE = 'swag_paypal_pos_sales_channel:create';
    private const PAYPAL_POS_SALES_CHANNEL_PRIVILEGE_DELETE = 'swag_paypal_pos_sales_channel:delete';
    private const PAYPAL_POS_SALES_CHANNEL_PRIVILEGE_RUN_READ = 'swag_paypal_pos_sales_channel_run:read';
    private const PAYPAL_POS_SALES_CHANNEL_PRIVILEGE_RUN_UPDATE = 'swag_paypal_pos_sales_channel_run:update';
    private const PAYPAL_POS_SALES_CHANNEL_PRIVILEGE_RUN_CREATE = 'swag_paypal_pos_sales_channel_run:create';
    private const PAYPAL_POS_SALES_CHANNEL_PRIVILEGE_RUN_DELETE = 'swag_paypal_pos_sales_channel_run:delete';
    private const PAYPAL_POS_SALES_CHANNEL_PRIVILEGE_RUN_LOG_READ = 'swag_paypal_pos_sales_channel_run_log:read';

    /**
     * @var ActivateDeactivate
     */
    private $activateDeactivate;

    /**
     * @Required
     */
    public function setActivateDeactivate(ActivateDeactivate $activateDeactivate): void
    {
        $this->activateDeactivate = $activateDeactivate;
    }

    public function install(InstallContext $installContext): void
    {
        /** @var EntityRepositoryInterface $systemConfigRepository */
        $systemConfigRepository = $this->container->get('system_config.repository');
        /** @var EntityRepositoryInterface $paymentRepository */
        $paymentRepository = $this->container->get('payment_method.repository');
        /** @var EntityRepositoryInterface $salesChannelRepository */
        $salesChannelRepository = $this->container->get('sales_channel.repository');
        /** @var EntityRepositoryInterface $ruleRepository */
        $ruleRepository = $this->container->get('rule.repository');
        /** @var EntityRepositoryInterface $countryRepository */
        $countryRepository = $this->container->get('country.repository');
        /** @var PluginIdProvider $pluginIdProvider */
        $pluginIdProvider = $this->container->get(PluginIdProvider::class);
        /** @var SystemConfigService $systemConfigService */
        $systemConfigService = $this->container->get(SystemConfigService::class);
        /** @var Connection $connection */
        $connection = $this->container->get(Connection::class);

        (new InstallUninstall(
            $systemConfigRepository,
            $paymentRepository,
            $salesChannelRepository,
            $ruleRepository,
            $countryRepository,
            $pluginIdProvider,
            $systemConfigService,
            $connection,
            static::class
        ))->install($installContext->getContext());

        parent::install($installContext);
    }

    public function uninstall(UninstallContext $uninstallContext): void
    {
        $context = $uninstallContext->getContext();
        /** @var EntityRepositoryInterface $paymentRepository */
        $paymentRepository = $this->container->get('payment_method.repository');
        /** @var EntityRepositoryInterface $salesChannelRepository */
        $salesChannelRepository = $this->container->get('sales_channel.repository');

        if ($uninstallContext->keepUserData()) {
            parent::uninstall($uninstallContext);

            return;
        }

        /** @var EntityRepositoryInterface $systemConfigRepository */
        $systemConfigRepository = $this->container->get('system_config.repository');
        /** @var EntityRepositoryInterface $countryRepository */
        $countryRepository = $this->container->get('country.repository');
        /** @var EntityRepositoryInterface $ruleRepository */
        $ruleRepository = $this->container->get('rule.repository');
        /** @var PluginIdProvider $pluginIdProvider */
        $pluginIdProvider = $this->container->get(PluginIdProvider::class);
        /** @var SystemConfigService $systemConfigService */
        $systemConfigService = $this->container->get(SystemConfigService::class);
        /** @var Connection $connection */
        $connection = $this->container->get(Connection::class);

        (new InstallUninstall(
            $systemConfigRepository,
            $paymentRepository,
            $salesChannelRepository,
            $ruleRepository,
            $countryRepository,
            $pluginIdProvider,
            $systemConfigService,
            $connection,
            static::class
        ))->uninstall($context);

        parent::uninstall($uninstallContext);
    }

    public function update(UpdateContext $updateContext): void
    {
        /** @var SystemConfigService $systemConfigService */
        $systemConfigService = $this->container->get(SystemConfigService::class);
        /** @var EntityRepositoryInterface $customFieldRepository */
        $customFieldRepository = $this->container->get(\sprintf('%s.repository', (new CustomFieldDefinition())->getEntityName()));
        /** @var EntityRepositoryInterface $paymentRepository */
        $paymentRepository = $this->container->get('payment_method.repository');
        /** @var WebhookService|null $webhookService */
        $webhookService = $this->container->get(WebhookService::class, ContainerInterface::NULL_ON_INVALID_REFERENCE);
        /** @var EntityRepositoryInterface $salesChannelRepository */
        $salesChannelRepository = $this->container->get('sales_channel.repository');
        /** @var EntityRepositoryInterface $salesChannelTypeRepository */
        $salesChannelTypeRepository = $this->container->get('sales_channel_type.repository');
        /** @var InformationDefaultService|null $informationDefaultService */
        $informationDefaultService = $this->container->get(InformationDefaultService::class, ContainerInterface::NULL_ON_INVALID_REFERENCE);
        /** @var EntityRepositoryInterface $shippingRepository */
        $shippingRepository = $this->container->get('shipping_method.repository');
        /** @var PosWebhookService|null $posWebhookService */
        $posWebhookService = $this->container->get(PosWebhookService::class, ContainerInterface::NULL_ON_INVALID_REFERENCE);

        (new Update(
            $systemConfigService,
            $paymentRepository,
            $customFieldRepository,
            $webhookService,
            $salesChannelRepository,
            $salesChannelTypeRepository,
            $informationDefaultService,
            $shippingRepository,
            $posWebhookService
        ))->update($updateContext);

        parent::update($updateContext);
    }

    public function activate(ActivateContext $activateContext): void
    {
        $this->activateDeactivate->activate($activateContext->getContext());

        parent::activate($activateContext);
    }

    public function deactivate(DeactivateContext $deactivateContext): void
    {
        $this->activateDeactivate->deactivate($deactivateContext->getContext());

        parent::deactivate($deactivateContext);
    }

    public function enrichPrivileges(): array
    {
        return [
            'sales_channel.viewer' => [
                self::PAYPAL_POS_SALES_CHANNEL_PRIVILEGE_READ,
                self::PAYPAL_POS_SALES_CHANNEL_PRIVILEGE_RUN_READ,
                self::PAYPAL_POS_SALES_CHANNEL_PRIVILEGE_RUN_UPDATE,
                self::PAYPAL_POS_SALES_CHANNEL_PRIVILEGE_RUN_CREATE,
                self::PAYPAL_POS_SALES_CHANNEL_PRIVILEGE_RUN_LOG_READ,
                'sales_channel_payment_method:read',
            ],
            'sales_channel.editor' => [
                self::PAYPAL_POS_SALES_CHANNEL_PRIVILEGE_UPDATE,
                self::PAYPAL_POS_SALES_CHANNEL_PRIVILEGE_RUN_DELETE,
                'payment_method:update',
            ],
            'sales_channel.creator' => [
                self::PAYPAL_POS_SALES_CHANNEL_PRIVILEGE_CREATE,
                'payment_method:create',
                'shipping_method:create',
                'delivery_time:create',
            ],
            'sales_channel.deleter' => [
                self::PAYPAL_POS_SALES_CHANNEL_PRIVILEGE_DELETE,
            ],
        ];
    }
}
