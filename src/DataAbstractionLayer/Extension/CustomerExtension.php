<?php declare(strict_types=1);
/*
 * (c) shopware AG <info@shopware.com>
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Swag\PayPal\DataAbstractionLayer\Extension;

use Shopware\Core\Checkout\Customer\CustomerDefinition;
use Shopware\Core\Framework\DataAbstractionLayer\EntityExtension;
use Shopware\Core\Framework\DataAbstractionLayer\Field\Flag\CascadeDelete;
use Shopware\Core\Framework\DataAbstractionLayer\Field\OneToManyAssociationField;
use Shopware\Core\Framework\DataAbstractionLayer\FieldCollection;
use Shopware\Core\Framework\Log\Package;
use Swag\PayPal\DataAbstractionLayer\VaultToken\VaultTokenDefinition;
use Swag\PayPal\DataAbstractionLayer\VaultTokenMapping\VaultTokenMappingDefinition;

#[Package('checkout')]
class CustomerExtension extends EntityExtension
{
    public const CUSTOMER_VAULT_TOKEN_EXTENSION = 'paypalVaultTokens';
    public const CUSTOMER_VAULT_TOKEN_MAPPING_EXTENSION = 'paypalVaultTokenMappings';

    public function extendFields(FieldCollection $collection): void
    {
        $collection->add(
            (new OneToManyAssociationField(
                self::CUSTOMER_VAULT_TOKEN_EXTENSION,
                VaultTokenDefinition::class,
                'customer_id'
            ))->addFlags(new CascadeDelete())
        );

        $collection->add(
            (new OneToManyAssociationField(
                self::CUSTOMER_VAULT_TOKEN_MAPPING_EXTENSION,
                VaultTokenMappingDefinition::class,
                'customer_id'
            ))->addFlags(new CascadeDelete())
        );
    }

    public function getDefinitionClass(): string
    {
        return CustomerDefinition::class;
    }
}
