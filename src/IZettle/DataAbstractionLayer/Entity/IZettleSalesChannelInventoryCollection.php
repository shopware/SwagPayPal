<?php declare(strict_types=1);
/*
 * (c) shopware AG <info@shopware.com>
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Swag\PayPal\IZettle\DataAbstractionLayer\Entity;

use Shopware\Core\Framework\DataAbstractionLayer\EntityCollection;

/**
 * @method void                                           add(IZettleSalesChannelInventoryEntity $entity)
 * @method void                                           set(string $key, IZettleSalesChannelInventoryEntity $entity)
 * @method \Generator<IZettleSalesChannelInventoryEntity> getIterator()
 * @method IZettleSalesChannelInventoryEntity[]           getElements()
 * @method IZettleSalesChannelInventoryEntity|null        get(string $key)
 * @method IZettleSalesChannelInventoryEntity|null        first()
 * @method IZettleSalesChannelInventoryEntity|null        last()
 */
class IZettleSalesChannelInventoryCollection extends EntityCollection
{
    protected function getExpectedClass(): string
    {
        return IZettleSalesChannelInventoryEntity::class;
    }
}
