<?php declare(strict_types=1);
/*
 * (c) shopware AG <info@shopware.com>
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Swag\PayPal\Pos\DataAbstractionLayer\Entity;

use Shopware\Core\Framework\DataAbstractionLayer\EntityCollection;

/**
 * @method void                                    add(PosSalesChannelRunLogEntity $entity)
 * @method void                                    set(string $key, PosSalesChannelRunLogEntity $entity)
 * @method \Generator<PosSalesChannelRunLogEntity> getIterator()
 * @method PosSalesChannelRunLogEntity[]           getElements()
 * @method PosSalesChannelRunLogEntity|null        get(string $key)
 * @method PosSalesChannelRunLogEntity|null        first()
 * @method PosSalesChannelRunLogEntity|null        last()
 */
class PosSalesChannelRunLogCollection extends EntityCollection
{
    protected function getExpectedClass(): string
    {
        return PosSalesChannelRunLogEntity::class;
    }
}
