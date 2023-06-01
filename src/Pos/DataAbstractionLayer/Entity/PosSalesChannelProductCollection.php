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
 * @extends EntityCollection<PosSalesChannelProductEntity>
 */
#[Package('checkout')]
class PosSalesChannelProductCollection extends EntityCollection
{
    public function hasProduct(string $uuid): bool
    {
        foreach ($this->getElements() as $element) {
            if ($element->getProductId() === $uuid) {
                return true;
            }
        }

        return false;
    }

    protected function getExpectedClass(): string
    {
        return PosSalesChannelProductEntity::class;
    }
}
