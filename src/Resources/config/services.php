<?php declare(strict_types=1);
/*
 * (c) shopware AG <info@shopware.com>
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

use Shopware\Core\Checkout\Customer\Aggregate\CustomerGroup\CustomerGroupDefinition;
use Shopware\Core\Checkout\Customer\CustomerDefinition;
use Shopware\Core\Checkout\Order\Aggregate\OrderAddress\OrderAddressDefinition;
use Shopware\Core\Checkout\Order\Aggregate\OrderLineItem\OrderLineItemDefinition;
use Shopware\Core\Checkout\Order\Aggregate\OrderTransaction\OrderTransactionDefinition;
use Shopware\Core\Checkout\Order\OrderDefinition;
use Shopware\Core\Checkout\Payment\PaymentMethodDefinition;
use Shopware\Core\Checkout\Shipping\ShippingMethodDefinition;
use Shopware\Core\Content\Category\CategoryDefinition;
use Shopware\Core\Content\Media\Aggregate\MediaFolder\MediaFolderDefinition;
use Shopware\Core\Content\Media\MediaDefinition;
use Shopware\Core\Content\Product\Aggregate\ProductVisibility\ProductVisibilityDefinition;
use Shopware\Core\Content\Product\ProductDefinition;
use Shopware\Core\Content\Rule\RuleDefinition;
use Shopware\Core\Framework\DataAbstractionLayer\EntityRepositoryInterface;
use Shopware\Core\Framework\DataAbstractionLayer\EntityRepositoryForwardCompatibilityDecorator;
use Shopware\Core\Framework\MessageQueue\ScheduledTask\ScheduledTaskDefinition;
use Shopware\Core\System\Country\Aggregate\CountryState\CountryStateDefinition;
use Shopware\Core\System\Country\CountryDefinition;
use Shopware\Core\System\Currency\CurrencyDefinition;
use Shopware\Core\System\DeliveryTime\DeliveryTimeDefinition;
use Shopware\Core\System\Language\LanguageDefinition;
use Shopware\Core\System\SalesChannel\Aggregate\SalesChannelType\SalesChannelTypeDefinition;
use Shopware\Core\System\SalesChannel\SalesChannelDefinition;
use Shopware\Core\System\Salutation\SalutationDefinition;
use Shopware\Core\System\StateMachine\Aggregation\StateMachineState\StateMachineStateDefinition;
use Shopware\Core\System\SystemConfig\SystemConfigDefinition;
use Swag\PayPal\Pos\DataAbstractionLayer\Entity\PosSalesChannelDefinition;
use Swag\PayPal\Pos\DataAbstractionLayer\Entity\PosSalesChannelInventoryDefinition;
use Swag\PayPal\Pos\DataAbstractionLayer\Entity\PosSalesChannelMediaDefinition;
use Swag\PayPal\Pos\DataAbstractionLayer\Entity\PosSalesChannelProductDefinition;
use Swag\PayPal\Pos\DataAbstractionLayer\Entity\PosSalesChannelRunDefinition;
use Swag\PayPal\Pos\DataAbstractionLayer\Entity\PosSalesChannelRunLogDefinition;
use Swag\PayPal\Util\Compatibility\EntityRepositoryDecorator;
use Symfony\Component\Config\FileLocator;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Definition;
use Symfony\Component\DependencyInjection\Loader\XmlFileLoader;
use Symfony\Component\DependencyInjection\Reference;

return static function (ContainerBuilder $container): void {
    $loader = new XmlFileLoader($container, new FileLocator(__DIR__ . '/services'));

    $loader->load('administration.xml');
    $loader->load('apm.xml');
    $loader->load('checkout.xml');
    $loader->load('client.xml');
    $loader->load('dispute.xml');
    $loader->load('express_checkout.xml');
    $loader->load('installment.xml');
    $loader->load('orders_api.xml');
    $loader->load('payments_api.xml');
    $loader->load('plus.xml');
    $loader->load('pui.xml');
    $loader->load('resource_v1.xml');
    $loader->load('resource_v2.xml');
    $loader->load('service_v1.xml');
    $loader->load('setting.xml');
    $loader->load('shipping.xml');
    $loader->load('spb_checkout.xml');
    $loader->load('storefront.xml');
    $loader->load('util.xml');
    $loader->load('webhook.xml');

    $loader->load('pos/api.xml');
    $loader->load('pos/command.xml');
    $loader->load('pos/dal.xml');
    $loader->load('pos/message_queue.xml');
    $loader->load('pos/run.xml');
    $loader->load('pos/schedule.xml');
    $loader->load('pos/setting.xml');
    $loader->load('pos/sync.xml');
    $loader->load('pos/webhook.xml');

    // Shopware 6.4 compatibility
    if (interface_exists(EntityRepositoryInterface::class) && !class_exists(EntityRepositoryForwardCompatibilityDecorator::class)) {
        $decoratedEntityNames = [
            CategoryDefinition::ENTITY_NAME,
            CountryDefinition::ENTITY_NAME,
            CountryStateDefinition::ENTITY_NAME,
            CurrencyDefinition::ENTITY_NAME,
            CustomerDefinition::ENTITY_NAME,
            CustomerGroupDefinition::ENTITY_NAME,
            DeliveryTimeDefinition::ENTITY_NAME,
            LanguageDefinition::ENTITY_NAME,
            MediaDefinition::ENTITY_NAME,
            MediaFolderDefinition::ENTITY_NAME,
            OrderDefinition::ENTITY_NAME,
            OrderAddressDefinition::ENTITY_NAME,
            OrderLineItemDefinition::ENTITY_NAME,
            OrderTransactionDefinition::ENTITY_NAME,
            PaymentMethodDefinition::ENTITY_NAME,
            PosSalesChannelDefinition::ENTITY_NAME,
            PosSalesChannelInventoryDefinition::ENTITY_NAME,
            PosSalesChannelMediaDefinition::ENTITY_NAME,
            PosSalesChannelProductDefinition::ENTITY_NAME,
            PosSalesChannelRunDefinition::ENTITY_NAME,
            PosSalesChannelRunLogDefinition::ENTITY_NAME,
            ProductDefinition::ENTITY_NAME,
            ProductVisibilityDefinition::ENTITY_NAME,
            RuleDefinition::ENTITY_NAME,
            SalesChannelDefinition::ENTITY_NAME,
            SalesChannelTypeDefinition::ENTITY_NAME,
            SalutationDefinition::ENTITY_NAME,
            ScheduledTaskDefinition::ENTITY_NAME,
            ShippingMethodDefinition::ENTITY_NAME,
            StateMachineStateDefinition::ENTITY_NAME,
            SystemConfigDefinition::ENTITY_NAME,
        ];

        $definitions = [];

        foreach ($decoratedEntityNames as $entityName) {
            $definition = new Definition(EntityRepositoryDecorator::class);
            $definition->setDecoratedService(sprintf('%s.repository', $entityName), null, PHP_INT_MIN);
            $definition->setArguments([new Reference('.inner')]);
            $definitions[sprintf('%s.repository.paypal_outer', $entityName)] = $definition;
        }

        $container->addDefinitions($definitions);
    }
};
