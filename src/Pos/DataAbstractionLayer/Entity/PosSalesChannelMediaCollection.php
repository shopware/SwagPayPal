<?php declare(strict_types=1);
/*
 * (c) shopware AG <info@shopware.com>
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Swag\PayPal\Pos\DataAbstractionLayer\Entity;

use Shopware\Core\Framework\DataAbstractionLayer\EntityCollection;
use Shopware\Core\Framework\Log\Package;

/**
 * @method void                                   add(PosSalesChannelMediaEntity $entity)
 * @method void                                   set(string $key, PosSalesChannelMediaEntity $entity)
 * @method \Generator<PosSalesChannelMediaEntity> getIterator()
 * @method PosSalesChannelMediaEntity[]           getElements()
 * @method PosSalesChannelMediaEntity|null        get(string $key)
 * @method PosSalesChannelMediaEntity|null        first()
 * @method PosSalesChannelMediaEntity|null        last()
 */
#[Package('checkout')]
class PosSalesChannelMediaCollection extends EntityCollection
{
    protected function getExpectedClass(): string
    {
        return PosSalesChannelMediaEntity::class;
    }
}
