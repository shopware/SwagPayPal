<?php declare(strict_types=1);
/*
 * (c) shopware AG <info@shopware.com>
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Swag\PayPal;

use Doctrine\DBAL\Connection;
use Shopware\Core\Checkout\Payment\PaymentMethodDefinition;
use Shopware\Core\Checkout\Shipping\ShippingMethodDefinition;
use Shopware\Core\Content\Media\Aggregate\MediaFolder\MediaFolderDefinition;
use Shopware\Core\Content\Media\File\FileSaver;
use Shopware\Core\Content\Media\MediaDefinition;
use Shopware\Core\Content\Rule\RuleDefinition;
use Shopware\Core\Framework\DataAbstractionLayer\EntityRepository;
use Shopware\Core\Framework\Log\Package;
use Shopware\Core\Framework\Plugin;
use Shopware\Core\Framework\Plugin\Context\ActivateContext;
use Shopware\Core\Framework\Plugin\Context\DeactivateContext;
use Shopware\Core\Framework\Plugin\Context\InstallContext;
use Shopware\Core\Framework\Plugin\Context\UninstallContext;
use Shopware\Core\Framework\Plugin\Context\UpdateContext;
use Shopware\Core\Framework\Plugin\Util\PluginIdProvider;
use Shopware\Core\System\CustomField\CustomFieldDefinition;
use Shopware\Core\System\SalesChannel\Aggregate\SalesChannelType\SalesChannelTypeDefinition;
use Shopware\Core\System\SalesChannel\SalesChannelDefinition;
use Shopware\Core\System\SystemConfig\SystemConfigDefinition;
use Shopware\Core\System\SystemConfig\SystemConfigService;
use Swag\PayPal\Pos\Setting\Service\InformationDefaultService;
use Swag\PayPal\Pos\Webhook\WebhookService as PosWebhookService;
use Swag\PayPal\Util\Lifecycle\ActivateDeactivate;
use Swag\PayPal\Util\Lifecycle\Installer\MediaInstaller;
use Swag\PayPal\Util\Lifecycle\Installer\PaymentMethodInstaller;
use Swag\PayPal\Util\Lifecycle\Installer\PosInstaller;
use Swag\PayPal\Util\Lifecycle\Installer\SettingsInstaller;
use Swag\PayPal\Util\Lifecycle\InstallUninstall;
use Swag\PayPal\Util\Lifecycle\Method\PaymentMethodDataRegistry;
use Swag\PayPal\Util\Lifecycle\State\PaymentMethodStateService;
use Swag\PayPal\Util\Lifecycle\Update;
use Swag\PayPal\Webhook\WebhookService;
use Symfony\Component\Config\FileLocator;
use Symfony\Component\Config\Loader\DelegatingLoader;
use Symfony\Component\Config\Loader\LoaderResolver;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\DependencyInjection\Exception\ServiceNotFoundException;
use Symfony\Component\DependencyInjection\Loader\DirectoryLoader;
use Symfony\Component\DependencyInjection\Loader\GlobFileLoader;
use Symfony\Component\DependencyInjection\Loader\YamlFileLoader;
use Symfony\Contracts\Service\Attribute\Required;

#[Package('checkout')]
class SwagPayPal extends Plugin
{
    public const ORDER_TRANSACTION_CUSTOM_FIELDS_PAYPAL_TRANSACTION_ID = 'swag_paypal_transaction_id';
    public const ORDER_TRANSACTION_CUSTOM_FIELDS_PAYPAL_TOKEN = 'swag_paypal_token';
    public const ORDER_TRANSACTION_CUSTOM_FIELDS_PAYPAL_PUI_INSTRUCTION = 'swag_paypal_pui_payment_instruction';
    public const ORDER_TRANSACTION_CUSTOM_FIELDS_PAYPAL_ORDER_ID = 'swag_paypal_order_id';
    public const ORDER_TRANSACTION_CUSTOM_FIELDS_PAYPAL_PARTNER_ATTRIBUTION_ID = 'swag_paypal_partner_attribution_id';
    public const ORDER_TRANSACTION_CUSTOM_FIELDS_PAYPAL_RESOURCE_ID = 'swag_paypal_resource_id';
    public const ORDER_TRANSACTION_CUSTOM_FIELDS_PAYPAL_IS_SANDBOX = 'swag_paypal_is_sandbox';
    public const SHIPPING_METHOD_CUSTOM_FIELDS_CARRIER = 'swag_paypal_carrier';
    public const SHIPPING_METHOD_CUSTOM_FIELDS_CARRIER_OTHER_NAME = 'swag_paypal_carrier_other_name';
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

    private ActivateDeactivate $activateDeactivate;

    #[Required]
    public function setActivateDeactivate(ActivateDeactivate $activateDeactivate): void
    {
        $this->activateDeactivate = $activateDeactivate;
    }

    public function install(InstallContext $installContext): void
    {
        $this->getInstaller()->install($installContext->getContext());

        parent::install($installContext);
    }

    public function uninstall(UninstallContext $uninstallContext): void
    {
        if (!$uninstallContext->keepUserData()) {
            $this->getInstaller()->uninstall($uninstallContext->getContext());
        }

        parent::uninstall($uninstallContext);
    }

    public function update(UpdateContext $updateContext): void
    {
        \assert($this->container instanceof ContainerInterface, 'Container is not set yet, please call setContainer() before calling boot(), see `platform/Core/Kernel.php:186`.');

        /** @var WebhookService|null $webhookService */
        $webhookService = $this->container->get(WebhookService::class, ContainerInterface::NULL_ON_INVALID_REFERENCE);
        /** @var InformationDefaultService|null $informationDefaultService */
        $informationDefaultService = $this->container->get(InformationDefaultService::class, ContainerInterface::NULL_ON_INVALID_REFERENCE);
        /** @var PosWebhookService|null $posWebhookService */
        $posWebhookService = $this->container->get(PosWebhookService::class, ContainerInterface::NULL_ON_INVALID_REFERENCE);
        /** @var PaymentMethodInstaller|null $paymentMethodInstaller */
        $paymentMethodInstaller = $this->container->get(PaymentMethodInstaller::class, ContainerInterface::NULL_ON_INVALID_REFERENCE);
        /** @var PaymentMethodStateService|null $paymentMethodStateService */
        $paymentMethodStateService = $this->container->get(PaymentMethodStateService::class, ContainerInterface::NULL_ON_INVALID_REFERENCE);
        /** @var MediaInstaller|null $mediaInstaller */
        $mediaInstaller = $this->container->get(MediaInstaller::class, ContainerInterface::NULL_ON_INVALID_REFERENCE);
        $paymentMethodDataRegistry = new PaymentMethodDataRegistry(
            $this->getRepository($this->container, PaymentMethodDefinition::ENTITY_NAME),
            $this->container
        );

        (new Update(
            $this->container->get(SystemConfigService::class),
            $this->getRepository($this->container, PaymentMethodDefinition::ENTITY_NAME),
            $this->getRepository($this->container, CustomFieldDefinition::ENTITY_NAME),
            $webhookService,
            $this->getRepository($this->container, SalesChannelDefinition::ENTITY_NAME),
            $this->getRepository($this->container, SalesChannelTypeDefinition::ENTITY_NAME),
            $informationDefaultService,
            $this->getRepository($this->container, ShippingMethodDefinition::ENTITY_NAME),
            $posWebhookService,
            $paymentMethodInstaller ?? new PaymentMethodInstaller(
                $this->getRepository($this->container, PaymentMethodDefinition::ENTITY_NAME),
                $this->getRepository($this->container, RuleDefinition::ENTITY_NAME),
                $this->container->get(PluginIdProvider::class),
                $paymentMethodDataRegistry,
                $mediaInstaller ?? new MediaInstaller(
                    $this->getRepository($this->container, MediaDefinition::ENTITY_NAME),
                    $this->getRepository($this->container, MediaFolderDefinition::ENTITY_NAME),
                    $this->getRepository($this->container, PaymentMethodDefinition::ENTITY_NAME),
                    $this->container->get(FileSaver::class),
                ),
            ),
            $paymentMethodStateService ?? new PaymentMethodStateService(
                $paymentMethodDataRegistry,
                $this->getRepository($this->container, PaymentMethodDefinition::ENTITY_NAME),
            ),
            $paymentMethodDataRegistry,
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

    public function build(ContainerBuilder $container): void
    {
        parent::build($container);

        $locator = new FileLocator('Resources/config');

        $resolver = new LoaderResolver([
            new YamlFileLoader($container, $locator),
            new GlobFileLoader($container, $locator),
            new DirectoryLoader($container, $locator),
        ]);

        $configLoader = new DelegatingLoader($resolver);

        $confDir = \rtrim($this->getPath(), '/') . '/Resources/config';

        $configLoader->load($confDir . '/{packages}/*.yaml', 'glob');
    }

    private function getInstaller(): InstallUninstall
    {
        \assert($this->container instanceof ContainerInterface, 'Container is not set yet, please call setContainer() before calling boot(), see `platform/Core/Kernel.php:186`.');

        return new InstallUninstall(
            new PaymentMethodInstaller(
                $this->getRepository($this->container, PaymentMethodDefinition::ENTITY_NAME),
                $this->getRepository($this->container, RuleDefinition::ENTITY_NAME),
                $this->container->get(PluginIdProvider::class),
                new PaymentMethodDataRegistry(
                    $this->getRepository($this->container, PaymentMethodDefinition::ENTITY_NAME),
                    $this->container,
                ),
                new MediaInstaller(
                    $this->getRepository($this->container, MediaDefinition::ENTITY_NAME),
                    $this->getRepository($this->container, MediaFolderDefinition::ENTITY_NAME),
                    $this->getRepository($this->container, PaymentMethodDefinition::ENTITY_NAME),
                    $this->container->get(FileSaver::class)
                ),
            ),
            new SettingsInstaller(
                $this->getRepository($this->container, SystemConfigDefinition::ENTITY_NAME),
                $this->container->get(SystemConfigService::class)
            ),
            new PosInstaller($this->container->get(Connection::class)),
        );
    }

    private function getRepository(ContainerInterface $container, string $entityName): EntityRepository
    {
        $repository = $container->get(\sprintf('%s.repository', $entityName), ContainerInterface::NULL_ON_INVALID_REFERENCE);

        if (!$repository instanceof EntityRepository) {
            throw new ServiceNotFoundException(\sprintf('%s.repository', $entityName));
        }

        return $repository;
    }
}
