<?php declare(strict_types=1);
/*
 * (c) shopware AG <info@shopware.com>
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Swag\PayPal\IZettle\DataAbstractionLayer\Entity;

use Shopware\Core\Content\Product\ProductDefinition;
use Shopware\Core\Framework\DataAbstractionLayer\Field\FkField;
use Shopware\Core\Framework\DataAbstractionLayer\Field\Flag\PrimaryKey;
use Shopware\Core\Framework\DataAbstractionLayer\Field\Flag\Required;
use Shopware\Core\Framework\DataAbstractionLayer\Field\ManyToOneAssociationField;
use Shopware\Core\Framework\DataAbstractionLayer\Field\ReferenceVersionField;
use Shopware\Core\Framework\DataAbstractionLayer\Field\StringField;
use Shopware\Core\Framework\DataAbstractionLayer\Field\UpdatedAtField;
use Shopware\Core\Framework\DataAbstractionLayer\FieldCollection;
use Shopware\Core\Framework\DataAbstractionLayer\MappingEntityDefinition;
use Shopware\Core\System\SalesChannel\SalesChannelDefinition;

class IZettleSalesChannelProductDefinition extends MappingEntityDefinition
{
    public function getEntityName(): string
    {
        return 'swag_paypal_izettle_sales_channel_product';
    }

    public function getEntityClass(): string
    {
        return IZettleSalesChannelProductEntity::class;
    }

    public function getCollectionClass(): string
    {
        return IZettleSalesChannelProductCollection::class;
    }

    protected function defineFields(): FieldCollection
    {
        return new FieldCollection([
            (new FkField('sales_channel_id', 'salesChannelId', SalesChannelDefinition::class))->addFlags(new PrimaryKey(), new Required()),
            (new FkField('product_id', 'productId', ProductDefinition::class))->addFlags(new PrimaryKey(), new Required()),
            (new ReferenceVersionField(ProductDefinition::class))->addFlags(new PrimaryKey(), new Required()),

            new ManyToOneAssociationField('bundle', 'bundle_id', SalesChannelDefinition::class),
            new ManyToOneAssociationField('product', 'product_id', ProductDefinition::class),

            (new StringField('checksum', 'checksum', 32))->addFlags(new Required()),
            new UpdatedAtField(),
        ]);
    }
}
