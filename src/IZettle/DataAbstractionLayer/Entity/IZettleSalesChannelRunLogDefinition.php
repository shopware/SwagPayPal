<?php declare(strict_types=1);
/*
 * (c) shopware AG <info@shopware.com>
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Swag\PayPal\IZettle\DataAbstractionLayer\Entity;

use Shopware\Core\Content\Product\ProductDefinition;
use Shopware\Core\Framework\DataAbstractionLayer\EntityDefinition;
use Shopware\Core\Framework\DataAbstractionLayer\Field\FkField;
use Shopware\Core\Framework\DataAbstractionLayer\Field\Flag\PrimaryKey;
use Shopware\Core\Framework\DataAbstractionLayer\Field\Flag\Required;
use Shopware\Core\Framework\DataAbstractionLayer\Field\IdField;
use Shopware\Core\Framework\DataAbstractionLayer\Field\IntField;
use Shopware\Core\Framework\DataAbstractionLayer\Field\LongTextField;
use Shopware\Core\Framework\DataAbstractionLayer\Field\ReferenceVersionField;
use Shopware\Core\Framework\DataAbstractionLayer\FieldCollection;

class IZettleSalesChannelRunLogDefinition extends EntityDefinition
{
    public function getEntityName(): string
    {
        return 'swag_paypal_izettle_sales_channel_run_log';
    }

    public function getEntityClass(): string
    {
        return IZettleSalesChannelRunLogEntity::class;
    }

    public function getCollectionClass(): string
    {
        return IZettleSalesChannelRunLogCollection::class;
    }

    protected function defineFields(): FieldCollection
    {
        return new FieldCollection([
            (new IdField('id', 'id'))->setFlags(new PrimaryKey(), new Required()),
            (new FkField('run_id', 'runId', IZettleSalesChannelRunDefinition::class))->addFlags(new Required()),

            (new IntField('level', 'level'))->addFlags(new Required()),
            (new LongTextField('message', 'message'))->addFlags(new Required()),
            (new FkField('product_id', 'productId', ProductDefinition::class)),
            (new ReferenceVersionField(ProductDefinition::class)),
        ]);
    }
}
