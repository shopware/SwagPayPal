<?php declare(strict_types=1);
/*
 * (c) shopware AG <info@shopware.com>
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Swag\PayPal\Pos\DataAbstractionLayer\Entity;

use Shopware\Core\Framework\DataAbstractionLayer\EntityDefinition;
use Shopware\Core\Framework\DataAbstractionLayer\Field\DateTimeField;
use Shopware\Core\Framework\DataAbstractionLayer\Field\FkField;
use Shopware\Core\Framework\DataAbstractionLayer\Field\Flag\CascadeDelete;
use Shopware\Core\Framework\DataAbstractionLayer\Field\Flag\PrimaryKey;
use Shopware\Core\Framework\DataAbstractionLayer\Field\Flag\Required;
use Shopware\Core\Framework\DataAbstractionLayer\Field\IdField;
use Shopware\Core\Framework\DataAbstractionLayer\Field\IntField;
use Shopware\Core\Framework\DataAbstractionLayer\Field\JsonField;
use Shopware\Core\Framework\DataAbstractionLayer\Field\OneToManyAssociationField;
use Shopware\Core\Framework\DataAbstractionLayer\Field\StringField;
use Shopware\Core\Framework\DataAbstractionLayer\FieldCollection;
use Shopware\Core\Framework\Log\Package;
use Shopware\Core\System\SalesChannel\SalesChannelDefinition;

#[Package('checkout')]
class PosSalesChannelRunDefinition extends EntityDefinition
{
    public const ENTITY_NAME = 'swag_paypal_pos_sales_channel_run';

    public const STATUS_IN_PROGRESS = 'in_progress';
    public const STATUS_CANCELLED = 'cancelled';
    public const STATUS_FAILED = 'failed';
    public const STATUS_FINISHED = 'finished';

    public function getEntityName(): string
    {
        return self::ENTITY_NAME;
    }

    public function getEntityClass(): string
    {
        return PosSalesChannelRunEntity::class;
    }

    public function getCollectionClass(): string
    {
        return PosSalesChannelRunCollection::class;
    }

    public function getDefaults(): array
    {
        return [
            'status' => self::STATUS_IN_PROGRESS,
            'messageCount' => 0,
            'stepIndex' => 0,
        ];
    }

    protected function defineFields(): FieldCollection
    {
        return new FieldCollection([
            (new IdField('id', 'id'))->addFlags(new PrimaryKey(), new Required()),
            (new FkField('sales_channel_id', 'salesChannelId', SalesChannelDefinition::class))->addFlags(new Required()),

            (new StringField('task', 'task', 16))->addFlags(new Required()),
            (new StringField('status', 'status'))->addFlags(new Required()),
            (new IntField('message_count', 'messageCount'))->addFlags(new Required()),
            (new IntField('step_index', 'stepIndex'))->addFlags(new Required()),
            (new JsonField('steps', 'steps'))->addFlags(new Required()),
            new DateTimeField('finished_at', 'finishedAt'),

            (new OneToManyAssociationField('logs', PosSalesChannelRunLogDefinition::class, 'run_id'))->addFlags(new CascadeDelete()),
        ]);
    }
}
