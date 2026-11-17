<?php declare(strict_types=1);
/*
 * (c) shopware AG <info@shopware.com>
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

use Swag\PayPal\DataAbstractionLayer\Extension\CustomerExtension;
use Swag\PayPal\DataAbstractionLayer\Extension\PaymentMethodExtension;
use Swag\PayPal\DataAbstractionLayer\VaultToken\VaultTokenDefinition;
use Swag\PayPal\DataAbstractionLayer\VaultTokenMapping\VaultTokenMappingDefinition;
use Symfony\Component\DependencyInjection\Loader\Configurator\ContainerConfigurator;

return static function (ContainerConfigurator $containerConfigurator): void {
    $services = $containerConfigurator->services();

    $services->set(VaultTokenDefinition::class)
        ->tag('shopware.entity.definition', ['entity' => 'swag_paypal_vault_token']);

    $services->set(VaultTokenMappingDefinition::class)
        ->tag('shopware.entity.definition', ['entity' => 'swag_paypal_vault_token_mapping']);

    $services->set(CustomerExtension::class)
        ->tag('shopware.entity.extension');

    $services->set(PaymentMethodExtension::class)
        ->tag('shopware.entity.extension');
};
