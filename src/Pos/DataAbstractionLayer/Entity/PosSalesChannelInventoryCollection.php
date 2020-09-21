<?php declare(strict_types=1);
/*
 * (c) shopware AG <info@shopware.com>
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Swag\PayPal\Pos\DataAbstractionLayer\Entity;

use Shopware\Core\Framework\DataAbstractionLayer\EntityCollection;

/**
 * @method void                                       add(PosSalesChannelInventoryEntity $entity)
 * @method void                                       set(string $key, PosSalesChannelInventoryEntity $entity)
 * @method \Generator<PosSalesChannelInventoryEntity> getIterator()
 * @method PosSalesChannelInventoryEntity[]           getElements()
 * @method PosSalesChannelInventoryEntity|null        get(string $key)
 * @method PosSalesChannelInventoryEntity|null        first()
 * @method PosSalesChannelInventoryEntity|null        last()
 */
class PosSalesChannelInventoryCollection extends EntityCollection
{
    protected function getExpectedClass(): string
    {
        return PosSalesChannelInventoryEntity::class;
    }
}
