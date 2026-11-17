<?php declare(strict_types=1);
/*
 * (c) shopware AG <info@shopware.com>
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

use Swag\PayPal\Pos\DataAbstractionLayer\Entity\PosSalesChannelDefinition;
use Swag\PayPal\Pos\DataAbstractionLayer\Entity\PosSalesChannelInventoryDefinition;
use Swag\PayPal\Pos\DataAbstractionLayer\Entity\PosSalesChannelMediaDefinition;
use Swag\PayPal\Pos\DataAbstractionLayer\Entity\PosSalesChannelProductDefinition;
use Swag\PayPal\Pos\DataAbstractionLayer\Entity\PosSalesChannelRunDefinition;
use Swag\PayPal\Pos\DataAbstractionLayer\Entity\PosSalesChannelRunLogDefinition;
use Swag\PayPal\Pos\DataAbstractionLayer\Extension\ProductExtension;
use Swag\PayPal\Pos\DataAbstractionLayer\Extension\ProductStreamExtension;
use Swag\PayPal\Pos\DataAbstractionLayer\Extension\SalesChannelExtension;
use Symfony\Component\DependencyInjection\Loader\Configurator\ContainerConfigurator;

return static function (ContainerConfigurator $containerConfigurator): void {
    $services = $containerConfigurator->services();

    $services->set(PosSalesChannelDefinition::class)
        ->tag('shopware.entity.definition', ['entity' => 'swag_paypal_pos_sales_channel']);

    $services->set(SalesChannelExtension::class)
        ->tag('shopware.entity.extension');

    $services->set(ProductExtension::class)
        ->tag('shopware.entity.extension');

    $services->set(ProductStreamExtension::class)
        ->tag('shopware.entity.extension');

    $services->set(PosSalesChannelInventoryDefinition::class)
        ->tag('shopware.entity.definition', ['entity' => 'swag_paypal_pos_sales_channel_inventory']);

    $services->set(PosSalesChannelRunDefinition::class)
        ->tag('shopware.entity.definition', ['entity' => 'swag_paypal_pos_sales_channel_run']);

    $services->set(PosSalesChannelRunLogDefinition::class)
        ->tag('shopware.entity.definition', ['entity' => 'swag_paypal_pos_sales_channel_run_log']);

    $services->set(PosSalesChannelMediaDefinition::class)
        ->tag('shopware.entity.definition', ['entity' => 'swag_paypal_pos_sales_channel_media']);

    $services->set(PosSalesChannelProductDefinition::class)
        ->tag('shopware.entity.definition', ['entity' => 'swag_paypal_pos_sales_channel_product']);
};
