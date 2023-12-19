<?php declare(strict_types=1);
/*
 * (c) shopware AG <info@shopware.com>
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Swag\PayPal\DataAbstractionLayer\VaultTokenMapping;

use Shopware\Core\Framework\DataAbstractionLayer\EntityCollection;
use Shopware\Core\Framework\Log\Package;
use Swag\PayPal\DataAbstractionLayer\VaultToken\VaultTokenEntity;

/**
 * @extends EntityCollection<VaultTokenEntity>
 */
#[Package('checkout')]
class VaultTokenMappingCollection extends EntityCollection
{
    protected function getExpectedClass(): string
    {
        return VaultTokenMappingEntity::class;
    }
}
