<?php declare(strict_types=1);
/*
 * (c) shopware AG <info@shopware.com>
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Swag\PayPal\IZettle\DataAbstractionLayer\Entity;

use Shopware\Core\Framework\DataAbstractionLayer\EntityCollection;

/**
 * @method void                                        add(IZettleSalesChannelRunLogEntity $entity)
 * @method void                                        set(string $key, IZettleSalesChannelRunLogEntity $entity)
 * @method \Generator<IZettleSalesChannelRunLogEntity> getIterator()
 * @method IZettleSalesChannelRunLogEntity[]           getElements()
 * @method IZettleSalesChannelRunLogEntity|null        get(string $key)
 * @method IZettleSalesChannelRunLogEntity|null        first()
 * @method IZettleSalesChannelRunLogEntity|null        last()
 */
class IZettleSalesChannelRunLogCollection extends EntityCollection
{
    protected function getExpectedClass(): string
    {
        return IZettleSalesChannelRunLogEntity::class;
    }
}
