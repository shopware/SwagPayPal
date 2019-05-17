<?php declare(strict_types=1);
/**
 * (c) shopware AG <info@shopware.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Swag\PayPal;

use Doctrine\DBAL\Connection;
use Shopware\Core\Framework\Context;
use Shopware\Core\Framework\CustomField\CustomFieldTypes;
use Shopware\Core\Framework\DataAbstractionLayer\EntityRepositoryInterface;
use Shopware\Core\Framework\DataAbstractionLayer\Search\Criteria;
use Shopware\Core\Framework\DataAbstractionLayer\Search\Filter\EqualsFilter;
use Shopware\Core\Framework\DataAbstractionLayer\Search\IdSearchResult;
use Shopware\Core\Framework\Plugin;
use Shopware\Core\Framework\Plugin\Context\ActivateContext;
use Shopware\Core\Framework\Plugin\Context\DeactivateContext;
use Shopware\Core\Framework\Plugin\Context\InstallContext;
use Shopware\Core\Framework\Plugin\Context\UninstallContext;
use Shopware\Core\Framework\Plugin\Util\PluginIdProvider;
use Swag\PayPal\Payment\PayPalPaymentHandler;
use Swag\PayPal\Util\PaymentMethodIdProvider;
use Symfony\Component\Config\FileLocator;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Loader\XmlFileLoader;

class SwagPayPal extends Plugin
{
    public const PAYPAL_TRANSACTION_CUSTOM_FIELD_NAME = 'swag_paypal_transaction_id';

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
    }

    public function getViewPaths(): array
    {
        $viewPaths = parent::getViewPaths();
        $viewPaths[] = 'Resources/views/storefront';

        return $viewPaths;
    }

    public function install(InstallContext $context): void
    {
        $this->addPaymentMethod($context->getContext());
    }

    public function uninstall(UninstallContext $context): void
    {
        $this->setPaymentMethodIsActive(false, $context->getContext());
        if ($context->keepUserData()) {
            parent::uninstall($context);

            return;
        }

        /** @var Connection $connection */
        $connection = $this->container->get(Connection::class);
        $connection->exec('
DELETE FROM `system_config` WHERE configuration_key LIKE "SwagPayPal.settings.%"
');

        parent::uninstall($context);
    }

    public function activate(ActivateContext $context): void
    {
        $shopwareContext = $context->getContext();
        $this->setPaymentMethodIsActive(true, $shopwareContext);
        $this->activateOrderTransactionCustomField($shopwareContext);

        parent::activate($context);
    }

    public function deactivate(DeactivateContext $context): void
    {
        $shopwareContext = $context->getContext();
        $this->setPaymentMethodIsActive(false, $shopwareContext);
        $this->deactivateOrderTransactionCustomField($shopwareContext);

        parent::deactivate($context);
    }

    private function addPaymentMethod(Context $context): void
    {
        /** @var PluginIdProvider $pluginIdProvider */
        $pluginIdProvider = $this->container->get(PluginIdProvider::class);
        $pluginId = $pluginIdProvider->getPluginIdByBaseClass($this->getClassName(), $context);
        /** @var EntityRepositoryInterface $paymentRepository */
        $paymentRepository = $this->container->get('payment_method.repository');
        $paymentMethodId = (new PaymentMethodIdProvider($paymentRepository))->getPayPalPaymentMethodId($context);

        if ($paymentMethodId !== null) {
            return;
        }

        $paypal = [
            'handlerIdentifier' => PayPalPaymentHandler::class,
            'name' => 'PayPal',
            'description' => 'Bezahlung per PayPal - einfach, schnell und sicher.',
            'pluginId' => $pluginId,
        ];

        $paymentRepository->create([$paypal], $context);
    }

    private function setPaymentMethodIsActive(bool $active, Context $context): void
    {
        /** @var EntityRepositoryInterface $paymentRepository */
        $paymentRepository = $this->container->get('payment_method.repository');
        $paymentMethodId = (new PaymentMethodIdProvider($paymentRepository))->getPayPalPaymentMethodId($context);

        if ($paymentMethodId === null) {
            return;
        }

        $paymentMethod = [
            'id' => $paymentMethodId,
            'active' => $active,
        ];

        $paymentRepository->update([$paymentMethod], $context);
    }

    private function activateOrderTransactionCustomField(Context $context): void
    {
        /** @var EntityRepositoryInterface $customFieldRepository */
        $customFieldRepository = $this->container->get('custom_field.repository');
        $customFieldIds = $this->getCustomFieldIds($customFieldRepository, $context);

        if ($customFieldIds->getTotal() !== 0) {
            return;
        }

        $customFieldRepository->upsert(
            [
                [
                    'name' => self::PAYPAL_TRANSACTION_CUSTOM_FIELD_NAME,
                    'type' => CustomFieldTypes::TEXT,
                ],
            ],
            $context
        );
    }

    private function deactivateOrderTransactionCustomField(Context $context): void
    {
        /** @var EntityRepositoryInterface $customFieldRepository */
        $customFieldRepository = $this->container->get('custom_field.repository');
        $customFieldIds = $this->getCustomFieldIds($customFieldRepository, $context);

        if ($customFieldIds->getTotal() !== 0) {
            return;
        }

        $ids = [];
        foreach ($customFieldIds->getIds() as $customFieldId) {
            $ids[] = ['id' => $customFieldId];
        }
        $customFieldRepository->delete($ids, $context);
    }

    private function getCustomFieldIds(EntityRepositoryInterface $customFieldRepository, Context $context): IdSearchResult
    {
        $criteria = new Criteria();
        $criteria->addFilter(new EqualsFilter('name', self::PAYPAL_TRANSACTION_CUSTOM_FIELD_NAME));

        return $customFieldRepository->searchIds($criteria, $context);
    }
}
