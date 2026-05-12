<?php declare(strict_types=1);
/*
 * (c) shopware AG <info@shopware.com>
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Swag\PayPal\Pos\DataAbstractionLayer\Entity;

use Shopware\Core\Content\Product\ProductDefinition;
use Shopware\Core\Framework\DataAbstractionLayer\EntityDefinition;
use Shopware\Core\Framework\DataAbstractionLayer\Field\FkField;
use Shopware\Core\Framework\DataAbstractionLayer\Field\Flag\PrimaryKey;
use Shopware\Core\Framework\DataAbstractionLayer\Field\Flag\Required;
use Shopware\Core\Framework\DataAbstractionLayer\Field\IdField;
use Shopware\Core\Framework\DataAbstractionLayer\Field\IntField;
use Shopware\Core\Framework\DataAbstractionLayer\Field\LongTextField;
use Shopware\Core\Framework\DataAbstractionLayer\Field\ManyToOneAssociationField;
use Shopware\Core\Framework\DataAbstractionLayer\Field\ReferenceVersionField;
use Shopware\Core\Framework\DataAbstractionLayer\FieldCollection;
use Shopware\Core\Framework\Log\Package;

#[Package('checkout')]
class PosSalesChannelRunLogDefinition extends EntityDefinition
{
    public const ENTITY_NAME = 'swag_paypal_pos_sales_channel_run_log';

    public function getEntityName(): string
    {
        return self::ENTITY_NAME;
    }

    public function getEntityClass(): string
    {
        return PosSalesChannelRunLogEntity::class;
    }

    public function getCollectionClass(): string
    {
        return PosSalesChannelRunLogCollection::class;
    }

    protected function defineFields(): FieldCollection
    {
        return new FieldCollection([
            (new IdField('id', 'id'))->addFlags(new PrimaryKey(), new Required()),
            (new FkField('run_id', 'runId', PosSalesChannelRunDefinition::class))->addFlags(new Required()),

            new ManyToOneAssociationField('posSalesChannelRun', 'run_id', PosSalesChannelRunDefinition::class),

            (new IntField('level', 'level'))->addFlags(new Required()),
            (new LongTextField('message', 'message'))->addFlags(new Required()),
            new FkField('product_id', 'productId', ProductDefinition::class),
            new ReferenceVersionField(ProductDefinition::class),
        ]);
    }
}
