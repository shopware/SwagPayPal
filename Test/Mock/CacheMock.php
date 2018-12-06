<?php declare(strict_types=1);
/**
 * (c) shopware AG <info@shopware.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace SwagPayPal\Test\Mock;

use Psr\Cache\CacheItemInterface;
use Psr\Cache\CacheItemPoolInterface;
use SwagPayPal\PayPal\Resource\TokenResource;
use SwagPayPal\Test\PayPal\Resource\TokenResourceTest;

class CacheMock implements CacheItemPoolInterface
{
    public function getItem($key): CacheItemInterface
    {
        if ($key === TokenResource::CACHE_ID . TokenResourceTest::SALES_CHANNEL_ID_WITH_TOKEN) {
            return new CacheItemWithTokenMock();
        }

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
