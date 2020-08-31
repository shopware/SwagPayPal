<?php declare(strict_types=1);
/*
 * (c) shopware AG <info@shopware.com>
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Swag\PayPal\Pos\DataAbstractionLayer\Entity;

use Shopware\Core\Content\ProductStream\ProductStreamDefinition;
use Shopware\Core\Framework\DataAbstractionLayer\EntityDefinition;
use Shopware\Core\Framework\DataAbstractionLayer\Field\BoolField;
use Shopware\Core\Framework\DataAbstractionLayer\Field\FkField;
use Shopware\Core\Framework\DataAbstractionLayer\Field\Flag\PrimaryKey;
use Shopware\Core\Framework\DataAbstractionLayer\Field\Flag\Required;
use Shopware\Core\Framework\DataAbstractionLayer\Field\Flag\RestrictDelete;
use Shopware\Core\Framework\DataAbstractionLayer\Field\IdField;
use Shopware\Core\Framework\DataAbstractionLayer\Field\ManyToOneAssociationField;
use Shopware\Core\Framework\DataAbstractionLayer\Field\OneToOneAssociationField;
use Shopware\Core\Framework\DataAbstractionLayer\Field\StringField;
use Shopware\Core\Framework\DataAbstractionLayer\FieldCollection;
use Shopware\Core\System\SalesChannel\SalesChannelDefinition;

class PosSalesChannelDefinition extends EntityDefinition
{
    /**
     * Limited by max. header size
     */
    private const MAX_APIKEY_SIZE = 8192;

    /**
     * Limited by sha256 hex length
     */
    private const MAX_WEBHOOK_KEY_SIZE = 64;

    public function getEntityName(): string
    {
        return 'swag_paypal_pos_sales_channel';
    }

    public function getEntityClass(): string
    {
        return PosSalesChannelEntity::class;
    }

    public function getCollectionClass(): string
    {
        return PosSalesChannelCollection::class;
    }

    protected function defineFields(): FieldCollection
    {
        return new FieldCollection([
            (new IdField('id', 'id'))->setFlags(new PrimaryKey(), new Required()),

            (new FkField('sales_channel_id', 'salesChannelId', SalesChannelDefinition::class))->addFlags(new Required()),
            new FkField('product_stream_id', 'productStreamId', ProductStreamDefinition::class),

            (new StringField('api_key', 'apiKey', self::MAX_APIKEY_SIZE))->setFlags(new Required()),
            (new StringField('media_domain', 'mediaDomain')),
            (new StringField('webhook_signing_key', 'webhookSigningKey', self::MAX_WEBHOOK_KEY_SIZE)),

            (new BoolField('sync_prices', 'syncPrices')),
            (new BoolField('replace', 'replace')),

            (new OneToOneAssociationField('salesChannel', 'sales_channel_id', 'id', SalesChannelDefinition::class, false))->addFlags(new RestrictDelete()),
            new ManyToOneAssociationField('productStream', 'product_stream_id', ProductStreamDefinition::class),
        ]);
    }
}
