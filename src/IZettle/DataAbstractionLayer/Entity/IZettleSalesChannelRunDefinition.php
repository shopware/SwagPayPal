<?php declare(strict_types=1);
/*
 * (c) shopware AG <info@shopware.com>
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Swag\PayPal\IZettle\DataAbstractionLayer\Entity;

use Shopware\Core\Framework\DataAbstractionLayer\EntityDefinition;
use Shopware\Core\Framework\DataAbstractionLayer\Field\FkField;
use Shopware\Core\Framework\DataAbstractionLayer\Field\Flag\CascadeDelete;
use Shopware\Core\Framework\DataAbstractionLayer\Field\Flag\PrimaryKey;
use Shopware\Core\Framework\DataAbstractionLayer\Field\Flag\Required;
use Shopware\Core\Framework\DataAbstractionLayer\Field\IdField;
use Shopware\Core\Framework\DataAbstractionLayer\Field\OneToManyAssociationField;
use Shopware\Core\Framework\DataAbstractionLayer\FieldCollection;
use Shopware\Core\System\SalesChannel\SalesChannelDefinition;

class IZettleSalesChannelRunDefinition extends EntityDefinition
{
    public function getEntityName(): string
    {
        return 'swag_paypal_izettle_sales_channel_run';
    }

    public function getEntityClass(): string
    {
        return IZettleSalesChannelRunEntity::class;
    }

    public function getCollectionClass(): string
    {
        return IZettleSalesChannelRunCollection::class;
    }

    protected function defineFields(): FieldCollection
    {
        return new FieldCollection([
            (new IdField('id', 'id'))->setFlags(new PrimaryKey(), new Required()),
            (new FkField('sales_channel_id', 'salesChannelId', SalesChannelDefinition::class))->addFlags(new Required()),

            (new OneToManyAssociationField('logs', IZettleSalesChannelRunLogDefinition::class, 'run_id'))->addFlags(new CascadeDelete()),
        ]);
    }
}
