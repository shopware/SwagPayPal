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
     * @var ActivateDeactivate
     */
    private $activateDeactivate;

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
        $loader->load('plus.xml');
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
            \get_class($this)
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

        (new InstallUninstall(
            $systemConfigRepository,
            $paymentRepository,
            $salesChannelRepository,
            $ruleRepository,
            $countryRepository,
            $this->container->get(PluginIdProvider::class),
            $this->container->get(SystemConfigService::class),
            \get_class($this)
        ))->uninstall($context);

        parent::uninstall($uninstallContext);
    }

    /**
     * @Required
     */
    public function setActivateDeactivate(ActivateDeactivate $activateDeactivate): void
    {
        $this->activateDeactivate = $activateDeactivate;
    }

    public function activate(ActivateContext $activateContext): void
    {
        parent::activate($activateContext);

        $this->activateDeactivate->activate($activateContext->getContext());
    }

    public function deactivate(DeactivateContext $deactivateContext): void
    {
        parent::deactivate($deactivateContext);

        $this->activateDeactivate->deactivate($deactivateContext->getContext());
    }
}
