<?php declare(strict_types=1);
/**
 * (c) shopware AG <info@shopware.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace SwagPayPal\Setting;

use Shopware\Core\Framework\DataAbstractionLayer\EntityDefinition;
use Shopware\Core\Framework\DataAbstractionLayer\Field\BoolField;
use Shopware\Core\Framework\DataAbstractionLayer\Field\CreatedAtField;
use Shopware\Core\Framework\DataAbstractionLayer\Field\IdField;
use Shopware\Core\Framework\DataAbstractionLayer\Field\StringField;
use Shopware\Core\Framework\DataAbstractionLayer\Field\UpdatedAtField;
use Shopware\Core\Framework\DataAbstractionLayer\FieldCollection;
use Shopware\Core\Framework\DataAbstractionLayer\Write\Flag\PrimaryKey;
use Shopware\Core\Framework\DataAbstractionLayer\Write\Flag\Required;

class SwagPayPalSettingGeneralDefinition extends EntityDefinition
{
    public static function getEntityName(): string
    {
        return 'swag_paypal_setting_general';
    }

    public static function defineFields(): FieldCollection
    {
        return new FieldCollection([
            (new IdField('id', 'id'))->setFlags(new PrimaryKey(), new Required()),
            new StringField('client_id', 'clientId'),
            new StringField('client_secret', 'clientSecret'),
            (new BoolField('sandbox', 'sandbox'))->setFlags(new Required()),
            (new StringField('intent', 'intent'))->setFlags(new Required()),
            new BoolField('submit_cart', 'submitCart'),
            new StringField('webhook_id', 'webhookId'),
            new StringField('webhook_execute_token', 'webhookExecuteToken'),
            new StringField('brand_name', 'brandName', 127),
            new StringField('landing_page', 'landingPage'),
            new CreatedAtField(),
            new UpdatedAtField(),
        ]);
    }

    public static function getCollectionClass(): string
    {
        return SwagPayPalSettingGeneralCollection::class;
    }

    public static function getEntityClass(): string
    {
        return SwagPayPalSettingGeneralEntity::class;
    }
}
