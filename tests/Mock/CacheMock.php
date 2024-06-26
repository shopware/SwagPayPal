<?php declare(strict_types=1);
/*
 * (c) shopware AG <info@shopware.com>
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Swag\PayPal\Test\Mock;

use Psr\Cache\CacheItemInterface;
use Psr\Cache\CacheItemPoolInterface;
use Shopware\Core\Framework\Log\Package;

/**
 * @internal
 */
#[Package('checkout')]
class CacheMock implements CacheItemPoolInterface
{
    public function getItem(string $key): CacheItemInterface
    {
        return new CacheItemMockTyped();
    }

    public function getItems(array $keys = []): array
    {
        return [];
    }

    public function hasItem(string $key): bool
    {
        return true;
    }

    public function clear(): bool
    {
        return true;
    }

    public function deleteItem(string $key): bool
    {
        return true;
    }

    public function deleteItems(array $keys): bool
    {
        return true;
    }

    public function save(CacheItemInterface $item): bool
    {
        return true;
    }

    public function saveDeferred(CacheItemInterface $item): bool
    {
        return true;
    }

    public function commit(): bool
    {
        return true;
    }
}
