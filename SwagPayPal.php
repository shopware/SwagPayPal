<?php declare(strict_types=1);
/**
 * (c) shopware AG <info@shopware.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace SwagPayPal;

use Doctrine\DBAL\Connection;
use Shopware\Core\Framework\Context;
use Shopware\Core\Framework\DataAbstractionLayer\RepositoryInterface;
use Shopware\Core\Framework\Plugin;
use Shopware\Core\Framework\Plugin\Context\ActivateContext;
use Shopware\Core\Framework\Plugin\Context\DeactivateContext;
use Shopware\Core\Framework\Plugin\Context\InstallContext;
use Shopware\Core\Framework\Plugin\Context\UninstallContext;
use SwagPayPal\Core\Checkout\Payment\Cart\PaymentHandler\PayPalPayment;
use Symfony\Component\Config\FileLocator;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Loader\XmlFileLoader;

class SwagPayPal extends Plugin
{
    public const PAYMENT_METHOD_PAYPAL_ID = 'b8759d49b8a244ab8283f4a53f3e81fd';

    /**
     * The technical name of the unified payment method.
     */
    public const PAYPAL_NEXT_PAYMENT_METHOD_NAME = 'SwagPayPal';

    public function __construct($active = true)
    {
        parent::__construct($active);
    }

    /**
     * {@inheritdoc}
     */
    public function build(ContainerBuilder $container): void
    {
        parent::build($container);

        $loader = new XmlFileLoader($container, new FileLocator(__DIR__ . '/DependencyInjection/'));
        $loader->load('paypal_resource.xml');
        $loader->load('services.xml');
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
        $this->setPaymentMethodIsActive(true, $context->getContext());
        parent::activate($context);
    }

    public function deactivate(DeactivateContext $context): void
    {
        $this->setPaymentMethodIsActive(false, $context->getContext());
        parent::deactivate($context);
    }

    private function addPaymentMethod(Context $context): void
    {
        /** @var RepositoryInterface $paymentRepository */
        $paymentRepository = $this->container->get('payment_method.repository');

        $paypal = [
            'id' => self::PAYMENT_METHOD_PAYPAL_ID,
            'technicalName' => self::PAYPAL_NEXT_PAYMENT_METHOD_NAME,
            'name' => 'PayPal',
            'additionalDescription' => 'Bezahlung per PayPal - einfach, schnell und sicher.',
            'class' => PayPalPayment::class,
            'active' => true,
        ];

        $paymentRepository->upsert([$paypal], $context);
    }

    private function setPaymentMethodIsActive(bool $active, Context $context): void
    {
        /** @var RepositoryInterface $paymentRepository */
        $paymentRepository = $this->container->get('payment_method.repository');

        $paymentMethod = [
            'id' => self::PAYMENT_METHOD_PAYPAL_ID,
            'active' => $active,
        ];

        $paymentRepository->update([$paymentMethod], $context);
    }
}
