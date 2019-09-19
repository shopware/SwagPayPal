<?php declare(strict_types=1);

namespace Swag\PayPal\Test\Mock;

use Psr\Cache\CacheItemInterface;
use Psr\Cache\CacheItemPoolInterface;

class CacheMock implements CacheItemPoolInterface
{
    public function getItem($key): CacheItemInterface
    {
        return new CacheItemMock();
    }

    public function getItems(array $keys = []): array
    {
    }

    public function hasItem($key): bool
    {
    }

    public function clear(): bool
    {
    }

    public function deleteItem($key): bool
    {
    }

    public function deleteItems(array $keys): bool
    {
    }

    public function save(CacheItemInterface $item): bool
    {
        return true;
    }

    public function saveDeferred(CacheItemInterface $item): bool
    {
    }

    public function commit(): bool
    {
    }
}
