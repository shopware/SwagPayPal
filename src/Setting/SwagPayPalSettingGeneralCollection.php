<?php declare(strict_types=1);
/**
 * (c) shopware AG <info@shopware.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Swag\PayPal\Setting;

use Shopware\Core\Framework\DataAbstractionLayer\EntityCollection;

/**
 * @method void                                add(SwagPayPalSettingGeneralEntity $entity)
 * @method void                                set(string $key, SwagPayPalSettingGeneralEntity $entity)
 * @method SwagPayPalSettingGeneralEntity[]    getIterator()
 * @method SwagPayPalSettingGeneralEntity[]    getElements()
 * @method SwagPayPalSettingGeneralEntity|null get(string $key)
 * @method SwagPayPalSettingGeneralEntity|null first()
 * @method SwagPayPalSettingGeneralEntity|null last()
 */
class SwagPayPalSettingGeneralCollection extends EntityCollection
{
    protected function getExpectedClass(): string
    {
        return SwagPayPalSettingGeneralEntity::class;
    }
}
