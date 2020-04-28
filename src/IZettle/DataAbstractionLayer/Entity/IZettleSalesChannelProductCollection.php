<?php declare(strict_types=1);
/*
 * (c) shopware AG <info@shopware.com>
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Swag\PayPal\IZettle\DataAbstractionLayer\Entity;

use Shopware\Core\Framework\DataAbstractionLayer\EntityCollection;

/**
 * @method void                                         add(IZettleSalesChannelProductEntity $entity)
 * @method void                                         set(string $key, IZettleSalesChannelProductEntity $entity)
 * @method \Generator<IZettleSalesChannelProductEntity> getIterator()
 * @method IZettleSalesChannelProductEntity[]           getElements()
 * @method IZettleSalesChannelProductEntity|null        get(string $key)
 * @method IZettleSalesChannelProductEntity|null        first()
 * @method IZettleSalesChannelProductEntity|null        last()
 */
class IZettleSalesChannelProductCollection extends EntityCollection
{
    protected function getExpectedClass(): string
    {
        return IZettleSalesChannelProductEntity::class;
    }
}
