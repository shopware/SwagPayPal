<?php declare(strict_types=1);

namespace Swag\PayPal;

use Shopware\Core\Framework\DataAbstractionLayer\EntityRepositoryInterface;
use Shopware\Core\Framework\Plugin;
use Shopware\Core\Framework\Plugin\Context\ActivateContext;
use Shopware\Core\Framework\Plugin\Context\DeactivateContext;
use Shopware\Core\Framework\Plugin\Context\InstallContext;
use Shopware\Core\Framework\Plugin\Context\UninstallContext;
use Shopware\Core\Framework\Plugin\Util\PluginIdProvider;
use Shopware\Core\System\SystemConfig\SystemConfigService;
use Swag\PayPal\Util\Lifecycle\ActivateDeactivate;
use Swag\PayPal\Util\Lifecycle\InstallUninstall;
use Symfony\Component\Config\FileLocator;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Loader\XmlFileLoader;

class SwagPayPal extends Plugin
{
    public const ORDER_TRANSACTION_CUSTOM_FIELDS_PAYPAL_TRANSACTION_ID = 'swag_paypal_transaction_id';
    public const ORDER_TRANSACTION_CUSTOM_FIELDS_PAYPAL_PUI_INSTRUCTION = 'swag_paypal_pui_payment_instruction';

    /**
     * {@inheritdoc}
     */
    public function build(ContainerBuilder $container): void
    {
        parent::build($container);

        $loader = new XmlFileLoader($container, new FileLocator(__DIR__ . '/DependencyInjection/'));
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

        // PayPal Plus was disabled with PT-10610
        // Will be removed with 1.0.0
        // $loader->load('plus.xml');
    }

    public function getViewPaths(): array
    {
        $viewPaths = parent::getViewPaths();
        $viewPaths[] = 'Resources/views/storefront';

        return $viewPaths;
    }

    public function getStorefrontScriptPath(): string
    {
        return 'Resources/dist/storefront/js';
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

        (new InstallUninstall(
            $systemConfigRepository,
            $paymentRepository,
            $salesChannelRepository,
            $ruleRepository,
            $countryRepository,
            $this->container->get(PluginIdProvider::class),
            $this->container->get(SystemConfigService::class),
            $this->getClassName()
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
        /** @var EntityRepositoryInterface $customFieldRepository */
        $customFieldRepository = $this->container->get('custom_field.repository');

        (new ActivateDeactivate(
            $paymentRepository,
            $salesChannelRepository,
            $customFieldRepository
        ))->deactivate($context);

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

        (new InstallUninstall(
            $systemConfigRepository,
            $paymentRepository,
            $salesChannelRepository,
            $ruleRepository,
            $countryRepository,
            $this->container->get(PluginIdProvider::class),
            $this->container->get(SystemConfigService::class),
            $this->getClassName()
        ))->uninstall($context);

        parent::uninstall($uninstallContext);
    }

    public function activate(ActivateContext $activateContext): void
    {
        /** @var EntityRepositoryInterface $paymentRepository */
        $paymentRepository = $this->container->get('payment_method.repository');
        /** @var EntityRepositoryInterface $salesChannelRepository */
        $salesChannelRepository = $this->container->get('sales_channel.repository');
        /** @var EntityRepositoryInterface $customFieldRepository */
        $customFieldRepository = $this->container->get('custom_field.repository');

        (new ActivateDeactivate(
            $paymentRepository,
            $salesChannelRepository,
            $customFieldRepository
        ))->activate($activateContext->getContext());

        parent::activate($activateContext);
    }

    public function deactivate(DeactivateContext $deactivateContext): void
    {
        /** @var EntityRepositoryInterface $paymentRepository */
        $paymentRepository = $this->container->get('payment_method.repository');
        /** @var EntityRepositoryInterface $salesChannelRepository */
        $salesChannelRepository = $this->container->get('sales_channel.repository');
        /** @var EntityRepositoryInterface $customFieldRepository */
        $customFieldRepository = $this->container->get('custom_field.repository');

        (new ActivateDeactivate(
            $paymentRepository,
            $salesChannelRepository,
            $customFieldRepository
        ))->deactivate($deactivateContext->getContext());

        parent::deactivate($deactivateContext);
    }
}
