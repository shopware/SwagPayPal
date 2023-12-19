<?php declare(strict_types=1);
/*
 * (c) shopware AG <info@shopware.com>
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Swag\PayPal\DataAbstractionLayer\VaultTokenMapping;

use Shopware\Core\Checkout\Customer\CustomerDefinition;
use Shopware\Core\Checkout\Payment\PaymentMethodDefinition;
use Shopware\Core\Framework\DataAbstractionLayer\EntityDefinition;
use Shopware\Core\Framework\DataAbstractionLayer\Field\FkField;
use Shopware\Core\Framework\DataAbstractionLayer\Field\Flag\ApiAware;
use Shopware\Core\Framework\DataAbstractionLayer\Field\Flag\PrimaryKey;
use Shopware\Core\Framework\DataAbstractionLayer\Field\Flag\Required;
use Shopware\Core\Framework\DataAbstractionLayer\Field\ManyToOneAssociationField;
use Shopware\Core\Framework\DataAbstractionLayer\Field\OneToOneAssociationField;
use Shopware\Core\Framework\DataAbstractionLayer\FieldCollection;
use Shopware\Core\Framework\Log\Package;
use Swag\PayPal\DataAbstractionLayer\VaultToken\VaultTokenDefinition;

#[Package('checkout')]
class VaultTokenMappingDefinition extends EntityDefinition
{
    final public const ENTITY_NAME = 'swag_paypal_vault_token_mapping';

    public function getEntityName(): string
    {
        return self::ENTITY_NAME;
    }

    public function getEntityClass(): string
    {
        return VaultTokenMappingEntity::class;
    }

    public function getCollectionClass(): string
    {
        return VaultTokenMappingCollection::class;
    }

    protected function getParentDefinitionClass(): ?string
    {
        return VaultTokenDefinition::class;
    }

    protected function defineFields(): FieldCollection
    {
        return new FieldCollection([
            (new FkField('customer_id', 'customerId', CustomerDefinition::class))->addFlags(new Required(), new PrimaryKey(), new ApiAware()),
            (new FkField('payment_method_id', 'paymentMethodId', PaymentMethodDefinition::class))->addFlags(new Required(), new PrimaryKey(), new ApiAware()),
            (new FkField('token_id', 'tokenId', VaultTokenDefinition::class))->addFlags(new Required(), new ApiAware()),
            (new ManyToOneAssociationField('customer', 'customer_id', CustomerDefinition::class))->addFlags(new ApiAware()),
            (new ManyToOneAssociationField('paymentMethod', 'payment_method_id', PaymentMethodDefinition::class))->addFlags(new ApiAware()),
            (new OneToOneAssociationField('token', 'token_id', 'id', VaultTokenDefinition::class, false))->addFlags(new ApiAware()),
        ]);
    }
}
