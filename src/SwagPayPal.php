<?php declare(strict_types=1);
/**
 * (c) shopware AG <info@shopware.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace SwagPayPal;

use Doctrine\DBAL\Connection;
use Shopware\Core\Framework\Attribute\AttributeTypes;
use Shopware\Core\Framework\Context;
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
use SwagPayPal\Payment\PayPalPaymentHandler;
use Symfony\Component\Config\FileLocator;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Loader\XmlFileLoader;

class SwagPayPal extends Plugin
{
    public const PAYPAL_TRANSACTION_ATTRIBUTE_NAME = 'swag_paypal_transaction_id';

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
        $loader->load('webhook.xml');
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
DROP TABLE IF EXISTS swag_paypal_setting_general;
');
        parent::uninstall($context);
    }

    public function activate(ActivateContext $context): void
    {
        $shopwareContext = $context->getContext();
        $this->setPaymentMethodIsActive(true, $shopwareContext);
        $this->activateOrderTransactionAttribute($shopwareContext);

        parent::activate($context);
    }

    public function deactivate(DeactivateContext $context): void
    {
        $shopwareContext = $context->getContext();
        $this->setPaymentMethodIsActive(false, $shopwareContext);
        $this->deactivateOrderTransactionAttribute($shopwareContext);

        parent::deactivate($context);
    }

    private function addPaymentMethod(Context $context): void
    {
        /** @var PluginIdProvider $pluginIdProvider */
        $pluginIdProvider = $this->container->get(PluginIdProvider::class);
        $pluginId = $pluginIdProvider->getPluginIdByBaseClass($this->getClassName(), $context);
        $paymentMethodId = $this->getPaymentMethodId($context);

        if ($paymentMethodId !== null) {
            return;
        }

        $paypal = [
            'handlerIdentifier' => PayPalPaymentHandler::class,
            'name' => 'PayPal',
            'description' => 'Bezahlung per PayPal - einfach, schnell und sicher.',
            'pluginId' => $pluginId,
        ];

        /** @var EntityRepositoryInterface $paymentRepository */
        $paymentRepository = $this->container->get('payment_method.repository');
        $paymentRepository->create([$paypal], $context);
    }

    private function setPaymentMethodIsActive(bool $active, Context $context): void
    {
        /** @var EntityRepositoryInterface $paymentRepository */
        $paymentRepository = $this->container->get('payment_method.repository');
        $paymentMethodId = $this->getPaymentMethodId($context);

        if ($paymentMethodId === null) {
            return;
        }

        $paymentMethod = [
            'id' => $paymentMethodId,
            'active' => $active,
        ];

        $paymentRepository->update([$paymentMethod], $context);
    }

    private function getPaymentMethodId(Context $context): ?string
    {
        /** @var EntityRepositoryInterface $paymentRepository */
        $paymentRepository = $this->container->get('payment_method.repository');

        $criteria = new Criteria();
        $criteria->addFilter(new EqualsFilter('handlerIdentifier', PayPalPaymentHandler::class));

        $result = $paymentRepository->searchIds($criteria, $context);
        if ($result->getTotal() === 0) {
            return null;
        }

        $paymentMethodIds = $result->getIds();

        return array_shift($paymentMethodIds);
    }

    private function activateOrderTransactionAttribute(Context $context): void
    {
        /** @var EntityRepositoryInterface $attributeRepository */
        $attributeRepository = $this->container->get('attribute.repository');
        $attributeIds = $this->getAttributeIds($attributeRepository, $context);

        if ($attributeIds->getTotal() !== 0) {
            return;
        }

        $attributeRepository->upsert(
            [
                [
                    'name' => self::PAYPAL_TRANSACTION_ATTRIBUTE_NAME,
                    'type' => AttributeTypes::TEXT,
                ],
            ],
            $context
        );
    }

    private function deactivateOrderTransactionAttribute(Context $context): void
    {
        /** @var EntityRepositoryInterface $attributeRepository */
        $attributeRepository = $this->container->get('attribute.repository');
        $attributeIds = $this->getAttributeIds($attributeRepository, $context);

        if ($attributeIds->getTotal() === 0) {
            return;
        }

        $ids = [];
        foreach ($attributeIds->getIds() as $attributeId) {
            $ids[] = ['id' => $attributeId];
        }
        $attributeRepository->delete($ids, $context);
    }

    private function getAttributeIds(EntityRepositoryInterface $attributeRepository, Context $context): IdSearchResult
    {
        $criteria = new Criteria();
        $criteria->addFilter(new EqualsFilter('name', self::PAYPAL_TRANSACTION_ATTRIBUTE_NAME));

        return $attributeRepository->searchIds($criteria, $context);
    }
}
