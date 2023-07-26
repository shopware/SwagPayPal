<?php declare(strict_types=1);
/*
 * (c) shopware AG <info@shopware.com>
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Swag\PayPal\DataAbstractionLayer\Extension;

use Shopware\Core\Checkout\Payment\PaymentMethodDefinition;
use Shopware\Core\Framework\DataAbstractionLayer\EntityExtension;
use Shopware\Core\Framework\DataAbstractionLayer\Field\Flag\CascadeDelete;
use Shopware\Core\Framework\DataAbstractionLayer\Field\OneToManyAssociationField;
use Shopware\Core\Framework\DataAbstractionLayer\FieldCollection;
use Shopware\Core\Framework\Log\Package;
use Swag\PayPal\DataAbstractionLayer\VaultToken\VaultTokenDefinition;
use Swag\PayPal\DataAbstractionLayer\VaultTokenMapping\VaultTokenMappingDefinition;

#[Package('checkout')]
class PaymentMethodExtension extends EntityExtension
{
    public const PAYMENT_METHOD_VAULT_TOKEN_EXTENSION = 'paypalVaultTokens';
    public const PAYMENT_METHOD_VAULT_TOKEN_MAPPING_EXTENSION = 'paypalVaultTokenMappings';

    public function extendFields(FieldCollection $collection): void
    {
        $collection->add(
            (new OneToManyAssociationField(
                self::PAYMENT_METHOD_VAULT_TOKEN_EXTENSION,
                VaultTokenDefinition::class,
                'payment_method_id'
            ))->addFlags(new CascadeDelete())
        );

        $collection->add(
            (new OneToManyAssociationField(
                self::PAYMENT_METHOD_VAULT_TOKEN_MAPPING_EXTENSION,
                VaultTokenMappingDefinition::class,
                'payment_method_id'
            ))->addFlags(new CascadeDelete())
        );
    }

    public function getDefinitionClass(): string
    {
        return PaymentMethodDefinition::class;
    }
}
