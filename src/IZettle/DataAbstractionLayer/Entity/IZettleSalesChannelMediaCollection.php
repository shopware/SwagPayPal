<?php declare(strict_types=1);
/*
 * (c) shopware AG <info@shopware.com>
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Swag\PayPal\IZettle\DataAbstractionLayer\Entity;

use Shopware\Core\Framework\DataAbstractionLayer\EntityCollection;

/**
 * @method void                                       add(IZettleSalesChannelMediaEntity $entity)
 * @method void                                       set(string $key, IZettleSalesChannelMediaEntity $entity)
 * @method \Generator<IZettleSalesChannelMediaEntity> getIterator()
 * @method IZettleSalesChannelMediaEntity[]           getElements()
 * @method IZettleSalesChannelMediaEntity|null        get(string $key)
 * @method IZettleSalesChannelMediaEntity|null        first()
 * @method IZettleSalesChannelMediaEntity|null        last()
 */
class IZettleSalesChannelMediaCollection extends EntityCollection
{
    protected function getExpectedClass(): string
    {
        return IZettleSalesChannelMediaEntity::class;
    }
}
