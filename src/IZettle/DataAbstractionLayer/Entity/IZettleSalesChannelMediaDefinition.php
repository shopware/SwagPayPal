<?php declare(strict_types=1);
/*
 * (c) shopware AG <info@shopware.com>
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Swag\PayPal\IZettle\DataAbstractionLayer\Entity;

use Shopware\Core\Content\Media\MediaDefinition;
use Shopware\Core\Framework\DataAbstractionLayer\Field\CreatedAtField;
use Shopware\Core\Framework\DataAbstractionLayer\Field\FkField;
use Shopware\Core\Framework\DataAbstractionLayer\Field\Flag\PrimaryKey;
use Shopware\Core\Framework\DataAbstractionLayer\Field\Flag\Required;
use Shopware\Core\Framework\DataAbstractionLayer\Field\ManyToOneAssociationField;
use Shopware\Core\Framework\DataAbstractionLayer\Field\StringField;
use Shopware\Core\Framework\DataAbstractionLayer\FieldCollection;
use Shopware\Core\Framework\DataAbstractionLayer\MappingEntityDefinition;
use Shopware\Core\System\SalesChannel\SalesChannelDefinition;

class IZettleSalesChannelMediaDefinition extends MappingEntityDefinition
{
    public function getEntityName(): string
    {
        return 'swag_paypal_izettle_sales_channel_media';
    }

    public function getEntityClass(): string
    {
        return IZettleSalesChannelMediaEntity::class;
    }

    public function getCollectionClass(): string
    {
        return IZettleSalesChannelMediaCollection::class;
    }

    protected function defineFields(): FieldCollection
    {
        return new FieldCollection([
            (new FkField('sales_channel_id', 'salesChannelId', SalesChannelDefinition::class))->addFlags(new PrimaryKey(), new Required()),
            (new FkField('media_id', 'mediaId', MediaDefinition::class))->addFlags(new PrimaryKey(), new Required()),

            new ManyToOneAssociationField('media', 'media_id', MediaDefinition::class),

            (new StringField('lookup_key', 'lookupKey')),
            (new StringField('url', 'url')),
            new CreatedAtField(),
        ]);
    }
}
