<?php declare(strict_types=1);
/*
 * (c) shopware AG <info@shopware.com>
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Swag\PayPal\Reporting\DataAbstractionLayer\TransactionReport;

use Shopware\Core\Checkout\Order\Aggregate\OrderTransaction\OrderTransactionDefinition;
use Shopware\Core\Framework\DataAbstractionLayer\EntityDefinition;
use Shopware\Core\Framework\DataAbstractionLayer\Field\FkField;
use Shopware\Core\Framework\DataAbstractionLayer\Field\Flag\PrimaryKey;
use Shopware\Core\Framework\DataAbstractionLayer\Field\Flag\Required;
use Shopware\Core\Framework\DataAbstractionLayer\Field\FloatField;
use Shopware\Core\Framework\DataAbstractionLayer\Field\ManyToOneAssociationField;
use Shopware\Core\Framework\DataAbstractionLayer\Field\ReferenceVersionField;
use Shopware\Core\Framework\DataAbstractionLayer\Field\StringField;
use Shopware\Core\Framework\DataAbstractionLayer\FieldCollection;
use Shopware\Core\Framework\Log\Package;

/**
 * @internal
 *
 * @codeCoverageIgnore
 */
#[Package('checkout')]
class TransactionReportDefinition extends EntityDefinition
{
    public const ENTITY_NAME = 'swag_paypal_transaction_report';

    public function getEntityName(): string
    {
        return self::ENTITY_NAME;
    }

    public function getEntityClass(): string
    {
        return TransactionReportEntity::class;
    }

    public function getCollectionClass(): string
    {
        return TransactionReportCollection::class;
    }

    protected function defineFields(): FieldCollection
    {
        return new FieldCollection([
            (new FkField('order_transaction_id', 'orderTransactionId', OrderTransactionDefinition::class))->addFlags(new PrimaryKey(), new Required()),
            (new ReferenceVersionField(OrderTransactionDefinition::class))->addFlags(new PrimaryKey(), new Required()),
            (new StringField('currency_iso', 'currencyIso'))->addFlags(new Required()),
            (new FloatField('total_price', 'totalPrice'))->addFlags(new Required()),

            new ManyToOneAssociationField('orderTransaction', 'order_transaction_id', OrderTransactionDefinition::class),
        ]);
    }
}
