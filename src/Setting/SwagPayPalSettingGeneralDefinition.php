<?php declare(strict_types=1);
/**
 * (c) shopware AG <info@shopware.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Swag\PayPal\Setting;

use Shopware\Core\Framework\DataAbstractionLayer\EntityDefinition;
use Shopware\Core\Framework\DataAbstractionLayer\Field\BoolField;
use Shopware\Core\Framework\DataAbstractionLayer\Field\CreatedAtField;
use Shopware\Core\Framework\DataAbstractionLayer\Field\Flag\PrimaryKey;
use Shopware\Core\Framework\DataAbstractionLayer\Field\Flag\Required;
use Shopware\Core\Framework\DataAbstractionLayer\Field\IdField;
use Shopware\Core\Framework\DataAbstractionLayer\Field\StringField;
use Shopware\Core\Framework\DataAbstractionLayer\Field\UpdatedAtField;
use Shopware\Core\Framework\DataAbstractionLayer\FieldCollection;

class SwagPayPalSettingGeneralDefinition extends EntityDefinition
{
    public function getEntityName(): string
    {
        return 'swag_paypal_setting_general';
    }

    public function defineFields(): FieldCollection
    {
        return new FieldCollection([
            (new IdField('id', 'id'))->addFlags(new PrimaryKey(), new Required()),
            (new StringField('client_id', 'clientId'))->addFlags(new Required()),
            (new StringField('client_secret', 'clientSecret'))->addFlags(new Required()),
            (new BoolField('sandbox', 'sandbox'))->addFlags(new Required()),
            (new StringField('intent', 'intent'))->addFlags(new Required()),
            new BoolField('submit_cart', 'submitCart'),
            new StringField('webhook_id', 'webhookId'),
            new StringField('webhook_execute_token', 'webhookExecuteToken'),
            new StringField('brand_name', 'brandName', 127),
            new StringField('landing_page', 'landingPage'),
            new BoolField('send_order_number', 'sendOrderNumber'),
            new StringField('order_number_prefix', 'orderNumberPrefix'),
            new CreatedAtField(),
            new UpdatedAtField(),
        ]);
    }

    public function getCollectionClass(): string
    {
        return SwagPayPalSettingGeneralCollection::class;
    }

    public function getEntityClass(): string
    {
        return SwagPayPalSettingGeneralEntity::class;
    }
}
