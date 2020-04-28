<?php declare(strict_types=1);
/*
 * (c) shopware AG <info@shopware.com>
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Swag\PayPal;

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
use Swag\PayPal\Util\Lifecycle\ActivateDeactivate;
use Swag\PayPal\Util\Lifecycle\InstallUninstall;
use Swag\PayPal\Util\Lifecycle\Update;
use Swag\PayPal\Webhook\WebhookService;
use Symfony\Component\Config\FileLocator;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\DependencyInjection\Loader\XmlFileLoader;

class SwagPayPal extends Plugin
{
    public const ORDER_TRANSACTION_CUSTOM_FIELDS_PAYPAL_TRANSACTION_ID = 'swag_paypal_transaction_id';
    public const ORDER_TRANSACTION_CUSTOM_FIELDS_PAYPAL_TOKEN = 'swag_paypal_token';
    public const ORDER_TRANSACTION_CUSTOM_FIELDS_PAYPAL_PUI_INSTRUCTION = 'swag_paypal_pui_payment_instruction';
    public const SALES_CHANNEL_TYPE_IZETTLE = '1ce0868f406d47d98cfe4b281e62f099';

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

    /**
     * {@inheritdoc}
     */
    public function build(ContainerBuilder $container): void
    {
        parent::build($container);

        $loader = new XmlFileLoader($container, new FileLocator([
            __DIR__ . '/DependencyInjection/',
            __DIR__ . '/IZettle/DependencyInjection/',
        ]));

        $loader->load('client.xml');
        $loader->load('paypal_payment.xml');
        $loader->load('resource.xml');
        $loader->load('setting.xml');
        $loader->load('util.xml');
        $loader->load('webhook.xml');
        $loader->load('express_checkout.xml');
        $loader->load('spb_checkout.xml');
        $loader->load('pui_checkout.xml');
        $loader->load('checkout.xml');
        $loader->load('plus.xml');
        $loader->load('installment.xml');
        $loader->load('izettle.xml');
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

        (new InstallUninstall(
            $systemConfigRepository,
            $paymentRepository,
            $salesChannelRepository,
            $ruleRepository,
            $countryRepository,
            $pluginIdProvider,
            $systemConfigService,
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

        (new InstallUninstall(
            $systemConfigRepository,
            $paymentRepository,
            $salesChannelRepository,
            $ruleRepository,
            $countryRepository,
            $pluginIdProvider,
            $systemConfigService,
            static::class
        ))->uninstall($context);

        parent::uninstall($uninstallContext);
    }

    public function update(UpdateContext $updateContext): void
    {
        /** @var SystemConfigService $systemConfigService */
        $systemConfigService = $this->container->get(SystemConfigService::class);
        /** @var EntityRepositoryInterface $customFieldRepository */
        $customFieldRepository = $this->container->get((new CustomFieldDefinition())->getEntityName() . '.repository');

        /** @var WebhookService|null $webhookService */
        $webhookService = $this->container->get(WebhookService::class, ContainerInterface::NULL_ON_INVALID_REFERENCE);

        (new Update($systemConfigService, $customFieldRepository, $webhookService))->update($updateContext);
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
}
