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
 * @method void                                 add(PosSalesChannelRunEntity $entity)
 * @method void                                 set(string $key, PosSalesChannelRunEntity $entity)
 * @method \Generator<PosSalesChannelRunEntity> getIterator()
 * @method PosSalesChannelRunEntity[]           getElements()
 * @method PosSalesChannelRunEntity|null        get(string $key)
 * @method PosSalesChannelRunEntity|null        first()
 * @method PosSalesChannelRunEntity|null        last()
 */
#[Package('checkout')]
class PosSalesChannelRunCollection extends EntityCollection
{
    protected function getExpectedClass(): string
    {
        return PosSalesChannelRunEntity::class;
    }
}
