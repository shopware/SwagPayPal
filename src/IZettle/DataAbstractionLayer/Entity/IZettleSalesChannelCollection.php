<?php declare(strict_types=1);
/*
 * (c) shopware AG <info@shopware.com>
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Swag\PayPal\IZettle\DataAbstractionLayer\Entity;

use Shopware\Core\Framework\DataAbstractionLayer\EntityCollection;

/**
 * @method void                                  add(IZettleSalesChannelEntity $entity)
 * @method void                                  set(string $key, IZettleSalesChannelEntity $entity)
 * @method \Generator<IZettleSalesChannelEntity> getIterator()
 * @method IZettleSalesChannelEntity[]           getElements()
 * @method IZettleSalesChannelEntity|null        get(string $key)
 * @method IZettleSalesChannelEntity|null        first()
 * @method IZettleSalesChannelEntity|null        last()
 */
class IZettleSalesChannelCollection extends EntityCollection
{
    protected function getExpectedClass(): string
    {
        return IZettleSalesChannelEntity::class;
    }
}
