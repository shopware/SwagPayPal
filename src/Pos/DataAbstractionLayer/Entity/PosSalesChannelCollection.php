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
 * @method void                              add(PosSalesChannelEntity $entity)
 * @method void                              set(string $key, PosSalesChannelEntity $entity)
 * @method \Generator<PosSalesChannelEntity> getIterator()
 * @method PosSalesChannelEntity[]           getElements()
 * @method PosSalesChannelEntity|null        get(string $key)
 * @method PosSalesChannelEntity|null        first()
 * @method PosSalesChannelEntity|null        last()
 */
#[Package('checkout')]
class PosSalesChannelCollection extends EntityCollection
{
    protected function getExpectedClass(): string
    {
        return PosSalesChannelEntity::class;
    }
}
