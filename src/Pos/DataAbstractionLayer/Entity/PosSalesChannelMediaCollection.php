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
 * @extends EntityCollection<PosSalesChannelMediaEntity>
 */
#[Package('checkout')]
class PosSalesChannelMediaCollection extends EntityCollection
{
    protected function getExpectedClass(): string
    {
        return PosSalesChannelMediaEntity::class;
    }
}
